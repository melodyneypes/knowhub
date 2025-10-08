<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guest') {
    header('Location: ../../login.php');
    exit();
}

require '../../db.php';

$user_id = $_SESSION['user']['id'];

// Fetch single guest info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_user = $stmt->get_result();
$logged_in_user = $result_user->fetch_assoc();
$stmt->close();

// Forum rooms (guests only see DOIT and BSIT)
$forum_rooms = [
    ['id' => 'doit', 'title' => 'DOIT'],
    ['id' => 'bsit', 'title' => 'BSIT Department']
];

// Validate selected forum
$forum_id = $_GET['forum_id'] ?? null;
if ($forum_id === null || !in_array($forum_id, array_column($forum_rooms, 'id'))) {
    $forum_id = 'doit';
}

// Fetch threads and user info
$stmt = $conn->prepare("SELECT threads.*, users.name, users.block, users.year_level, users.picture 
                        FROM threads
                        INNER JOIN users ON threads.user_id = users.id
                        WHERE threads.forum_id = ?
                        ORDER BY threads.created_at DESC");
$stmt->bind_param("s", $forum_id);
$stmt->execute();
$result_threads = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threads - <?php echo htmlspecialchars($forum_id); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .sidebar .nav-link {
            color: #333;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #e9ecef;
            color: #126682d1;
        }
        .profile-img {
            max-width: 60px;
            margin: 20px auto 10px;
            display: block;
            border-radius: 50%;
            border: 2px solid #126682d1;
        }
        .main-container { margin-top: 2rem; }
        .left-sidebar {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .post-container {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            border-left: 4px solid #126682d1;
            transition: transform 0.2s ease;
        }
        .post-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .post-header img {
            width: 50px; 
            height: 50px;
            border-radius: 50%;
            margin-right: 1rem;
            border: 2px solid #e9ecef;
        }
        .post-content { 
            margin-top: 1rem; 
            margin-left: 60px; 
            line-height: 1.6;
        }
        .reply-link, .edit-link { 
            font-weight: bold; 
            color: #1a73e8; 
            text-decoration: none; 
        }
        .delete-link { 
            color: #dc3545; 
            font-weight: bold; 
            text-decoration: none; 
        }
        .forum-selector {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }
        .forum-selector h5 {
            color: #126682d1;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .forum-selector a {
            display: block;
            padding: 10px 15px;
            color: #495057;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: all 0.2s;
        }
        .forum-selector a:hover, .forum-selector a.active {
            background-color: #e9ecef;
            color: #126682d1;
            font-weight: 500;
        }
        .user-info-card {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #126682d1 0%, #1a73e8 100%);
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .user-info-card img {
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .post-form {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 30px;
        }
        .post-form textarea {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
        }
        .post-form textarea:focus {
            border-color: #126682d1;
            box-shadow: 0 0 0 0.2rem rgba(18, 102, 130, 0.25);
        }
        .thread-meta {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .nav-links li {
            list-style: none;
            margin-bottom: 8px;
        }
        .nav-links a {
            color: #495057;
            text-decoration: none;
            padding: 8px 12px;
            display: block;
            border-radius: 5px;
            transition: all 0.2s;
        }
        .nav-links a:hover {
            background-color: #f8f9fa;
        }
        .active-link a {
            background-color: #126682d1;
            color: white !important;
        }
        .no-threads {
            text-align: center;
            padding: 40px 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .no-threads i {
            font-size: 3rem;
            color: #e9ecef;
            margin-bottom: 20px;
        }
        .thread-title {
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }
        .thread-author {
            font-weight: 500;
            color: #126682d1;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
            <div class="text-center mb-4">
                <img src="<?php echo htmlspecialchars($logged_in_user['picture']); ?>" alt="Profile Picture" class="profile-img">
                <h5 class="mb-1"><?php echo htmlspecialchars($logged_in_user['name']); ?></h5>
                <small class="text-muted">Guest User</small>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard-guests.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a class="nav-link active" href="threads-guests.php"><i class="bi bi-chat-dots me-2"></i> Forums</a>
                <a class="nav-link" href="#"><i class="bi bi-folder me-2"></i> Resources</a>
                <a class="nav-link" href="#"><i class="bi bi-cloud-arrow-up me-2"></i> My Uploads</a>
                <a class="nav-link text-danger" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </nav>
            
            <div class="mt-auto text-center p-3">
                <small class="text-muted">© 2025 KnowHub Archive System</small>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <div class="container-fluid py-4">
                <div class="row">
                    <div class="col-md-3">
                        <!-- User Info Card -->
                        <div class="user-info-card mb-4">
                            <img src="<?php echo htmlspecialchars($logged_in_user['picture']); ?>" class="img-fluid rounded-circle mb-3" style="max-width: 80px;">
                            <h5><?php echo htmlspecialchars($logged_in_user['name']); ?></h5>
                            <p class="mb-0"><i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($logged_in_user['email']); ?></p>
                            <span class="badge bg-light text-dark mt-2">Guest</span>
                        </div>

                        <!-- Forum Rooms -->
                        <div class="forum-selector">
                            <h5><i class="bi bi-collection me-2"></i> Forum Rooms</h5>
                            <div class="nav-links">
                                <?php foreach ($forum_rooms as $forum): ?>
                                    <a href="threads-guests.php?forum_id=<?php echo urlencode($forum['id']); ?>" 
                                       class="<?php echo ($forum['id'] === $forum_id) ? 'active' : ''; ?>">
                                        <i class="bi bi-folder me-2"></i> <?php echo htmlspecialchars($forum['title']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9">
                        <!-- Forum Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="mb-0">/<?php echo htmlspecialchars(strtoupper($forum_id)); ?></h2>
                            <span class="badge bg-primary fs-6"><?php echo $result_threads->num_rows; ?> Threads</span>
                        </div>

                        <!-- Post Form -->
                        <div class="post-form">
                            <form action="../../create_thread.php" method="POST">
                                <input type="hidden" name="forum_id" value="<?php echo htmlspecialchars($forum_id); ?>">
                                <div class="d-flex align-items-start mb-3">
                                    <img src="<?php echo htmlspecialchars($logged_in_user['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle me-3" style="max-width: 50px;">
                                    <div class="flex-grow-1">
                                        <textarea name="description" class="form-control" placeholder="What would you like to discuss?" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-1"></i> Post Thread
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Threads -->
                        <?php if ($result_threads->num_rows > 0): ?>
                            <?php while ($row = $result_threads->fetch_assoc()): ?>
                                <div class="post-container" id="thread-<?php echo htmlspecialchars($row['id']); ?>">
                                    <div class="d-flex align-items-start">
                                        <img src="<?php echo htmlspecialchars($row['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle me-3" style="max-width: 50px;">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h5 class="thread-title mb-0"><?php echo htmlspecialchars($row['name']); ?></h5>
                                                    <small class="thread-meta">
                                                        <?php if (!empty($row['year_level'])): ?>
                                                            <?php echo htmlspecialchars($row['year_level']); ?>
                                                            <?php if (!empty($row['block'])): ?>
                                                                - Block <?php echo htmlspecialchars($row['block']); ?>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                                </small>
                                            </div>
                                            
                                            <div class="post-content mt-3">
                                                <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                            </div>
                                            
                                            <div class="d-flex mt-3">
                                                <a href="#" class="text-muted me-3"><i class="bi bi-chat me-1"></i> Reply</a>
                                                <a href="#" class="text-muted"><i class="bi bi-share me-1"></i> Share</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-threads">
                                <i class="bi bi-chat-dots"></i>
                                <h4>No Threads Found</h4>
                                <p class="mb-0">Be the first to start a discussion in this forum!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>