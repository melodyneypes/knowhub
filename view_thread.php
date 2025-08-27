<?php
// filepath: E:\CAP101-DANG FILES\archive-system\view_thread.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Check for the thread ID from the URL
$thread_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$thread_id) {
    header('Location: threads.php');
    exit();
}

// Fetch the main thread and its author
$stmt_thread = $conn->prepare("SELECT threads.*, users.name, users.year_level, users.block
                             FROM threads
                             INNER JOIN users ON threads.id = users.id
                             WHERE threads.id = ?");
$stmt_thread->bind_param("i", $thread_id);
$stmt_thread->execute();
$result_thread = $stmt_thread->get_result();
$main_thread = $result_thread->fetch_assoc();
$stmt_thread->close();

if (!$main_thread) {
    echo "Thread not found.";
    exit();
}

// Fetch all replies for this thread
$stmt_replies = $conn->prepare("SELECT replies.*, users.name, users.year_level, users.block
                              FROM replies
                              INNER JOIN users ON replies.user_id = users.id
                              WHERE replies.thread_id = ?
                              ORDER BY replies.created_at ASC");
$stmt_replies->bind_param("i", $thread_id);
$stmt_replies->execute();
$result_replies = $stmt_replies->get_result();
$stmt_replies->close();

// Get the logged-in user's details for displaying their profile picture and name
$logged_in_user_id = $_SESSION['user']['id'];
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $logged_in_user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$logged_in_user = $result_user->fetch_assoc();
$stmt_user->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thread: <?php echo htmlspecialchars($main_thread['title']); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .main-container {
            margin-top: 2rem;
        }
        .post-container, .reply-container {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .post-header, .reply-header {
            display: flex;
            align-items: center;
        }
        .post-header img, .reply-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
        }
        .reply-form-container {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="post-container">
            <div class="post-header">
                <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 50px;">
                <div>
                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($main_thread['name']); ?></h5>
                    <small class="text-muted"><?php echo htmlspecialchars($main_thread['year_level']); ?> Year, Block <?php echo htmlspecialchars($main_thread['block']); ?></small>
                </div>
            </div>
            <hr>
            <h3><?php echo htmlspecialchars($main_thread['title']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($main_thread['description'])); ?></p>
        </div>

        <h4 class="mt-5">Replies</h4>
        <?php if ($result_replies->num_rows > 0): ?>
            <?php while ($reply = $result_replies->fetch_assoc()): ?>
                <div class="reply-container">
                    <div class="reply-header">
                        <img src="https://via.placeholder.com/40" alt="Profile Picture">
                        <div>
                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($reply['full_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($reply['year_level']); ?> Year, Block <?php echo htmlspecialchars($reply['block']); ?></small>
                        </div>
                    </div>
                    <hr>
                    <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No replies yet. Be the first to reply!</p>
        <?php endif; ?>

        <div class="reply-form-container post-container">
            <h5>Leave a Reply</h5>
            <form action="create_reply.php" method="POST">
                <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                <div class="mb-3">
                    <textarea class="form-control" name="content" rows="4" placeholder="Write your reply here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Post Reply</button>
            </form>
        </div>
    </div>
</body>
</html>