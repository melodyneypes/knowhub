<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Get room ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid room ID.";
    exit();
}
$room_id = intval($_GET['id']);

// Check if user is a member of this room
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT r.name as room_name, s.name as subject_name, s.id as subject_id 
                        FROM rooms r 
                        JOIN subjects s ON r.subject_id = s.id 
                        WHERE r.id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Room not found.";
    exit();
}

$room_info = $result->fetch_assoc();
$stmt->close();

// Check if user is member of this room
$stmt = $conn->prepare("SELECT id FROM room_members WHERE room_id = ? AND user_id = ?");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "You don't have access to this room.";
    exit();
}
$stmt->close();

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $content = trim($_POST['message']);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO messages (room_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $room_id, $user_id, $content);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: room.php?id=$room_id");
    exit();
}

// Fetch messages for this room
$messages = [];
$stmt = $conn->prepare(
    "SELECT m.content, m.created_at, u.name as user_name 
     FROM messages m 
     JOIN users u ON m.user_id = u.id 
     WHERE m.room_id = ? 
     ORDER BY m.created_at ASC"
);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($room_info['room_name']); ?> - <?php echo htmlspecialchars($room_info['subject_name']); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .message-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .message-user {
            font-weight: bold;
            color: #0d6efd;
        }
        .message-time {
            font-size: 0.8em;
            color: #6c757d;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard-instructor.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="subjects-handled.php">My Handled Subjects</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="threads.php">Forums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="external-instructor.php">External Resources</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container mt-5">
    <a href="specific-subject.php?id=<?php echo $room_info['subject_id']; ?>" class="btn btn-secondary mb-3">&larr; Back to Subject</a>
    
    <div class="card mb-4">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($room_info['room_name']); ?></h2>
            <p class="mb-0">Subject: <?php echo htmlspecialchars($room_info['subject_name']); ?></p>
        </div>
        <div class="card-body">
            <div class="message-container" id="messageContainer">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message">
                            <div class="message-user"><?php echo htmlspecialchars($message['user_name']); ?></div>
                            <div><?php echo htmlspecialchars($message['content']); ?></div>
                            <div class="message-time"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No messages yet. Be the first to start the conversation!</p>
                <?php endif; ?>
            </div>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="message" class="form-label">Your Message</label>
                    <textarea class="form-control" id="message" name="message" rows="3" placeholder="Type your message here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<script>
    // Auto-scroll to bottom of message container
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.scrollTop = messageContainer.scrollHeight;
</script>
</body>
</html>