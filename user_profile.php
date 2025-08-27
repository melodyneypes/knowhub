<?php
// user_profile.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Get the user whose profile we want to view
$profile_user_id = $_GET['user_id'] ?? null;

if (!$profile_user_id) {
    header('Location: threads.php');
    exit();
}

// Fetch profile user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$result_user = $stmt->get_result();
$profile_user = $result_user->fetch_assoc();
$stmt->close();

if (!$profile_user) {
    header('Location: threads.php');
    exit();
}

// Fetch user's threads
$stmt = $conn->prepare("SELECT threads.*, users.name as name, users.block, users.year_level, users.picture 
                        FROM threads
                        INNER JOIN users ON threads.user_id = users.id
                        WHERE threads.user_id = ? 
                        ORDER BY threads.created_at DESC");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$user_threads = $stmt->get_result();
$stmt->close();

// Fetch user's replies
$stmt = $conn->prepare("SELECT replies.*, threads.id as thread_id, threads.description as thread_description, threads.forum_id
                        FROM replies 
                        INNER JOIN threads ON replies.thread_id = threads.id
                        WHERE replies.user_id = ? 
                        ORDER BY replies.created_at DESC");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$user_replies = $stmt->get_result();
$stmt->close();

// Fetch user's uploaded resources (checking for possible table names)
$user_resources = null;
$tables_to_check = ['resources', 'documents', 'files'];
$resource_table = '';

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $resource_table = $table;
        break;
    }
}

if ($resource_table) {
    $stmt = $conn->prepare("SELECT * FROM `$resource_table` WHERE uploader_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $profile_user_id);
    $stmt->execute();
    $user_resources = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['name']); ?>'s Profile</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .main-container {
            margin-top: 2rem;
        }
        .profile-header {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
        .post-container {
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .resource-card {
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.2s;
            cursor: pointer;
        }
        .resource-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .reply-container {
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .reply-container:hover {
            background-color: #f8f9fa;
        }
        .clickable-card {
            position: relative;
        }
        .clickable-card .card-link {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
        }
        .clickable-card-content {
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard-<?php echo $user_role === 'student' ? 'student' : 'admin'; ?>.php">Home</a>
                </li>
                <?php if ($user_role === 'student'): ?>
                <li>
                    <a class="nav-link" href="dashboard-student.php">My Subjects</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="browse.php">Browse</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="threads.php">Forums</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="external.php">External Resources</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container main-container">
        <div class="profile-header text-center">
            <img src="<?php echo htmlspecialchars($profile_user['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle profile-pic">
            <h2><?php echo htmlspecialchars($profile_user['name']); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($profile_user['email']); ?></p>
            
            <?php if ($profile_user['role'] !== 'admin'): ?>
                <p>
                    <?php echo htmlspecialchars($profile_user['year_level']); ?> Year<br>
                    Block <?php echo htmlspecialchars($profile_user['block']); ?><br>
                    <?php if ($profile_user['student_number']): ?>
                        Student Number: <?php echo htmlspecialchars($profile_user['student_number']); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            
        </div>
        
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">Posts</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="replies-tab" data-bs-toggle="tab" data-bs-target="#replies" type="button" role="tab">Replies</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button" role="tab">Resources</button>
            </li>
        </ul>
        
        <div class="tab-content" id="profileTabsContent">
            <!-- Posts Tab -->
            <div class="tab-pane fade show active" id="posts" role="tabpanel">
                <div class="mt-4">
                    <?php if ($user_threads->num_rows > 0): ?>
                        <?php while ($thread = $user_threads->fetch_assoc()): ?>
                            <div class="post-container">
                                <div class="d-flex align-items-start">
                                    <img src="<?php echo htmlspecialchars($thread['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 40px;">
                                    <div class="ms-2">
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($thread['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($thread['year_level']); ?> Year, Block <?php echo htmlspecialchars($thread['block']); ?></small>
                                        <small class="d-block text-muted"><?php echo date('M j, Y \a\t g:i A', strtotime($thread['created_at'])); ?></small>
                                    </div>
                                </div>
                                <div class="post-content ms-5">
                                    <p><?php echo htmlspecialchars($thread['description']); ?></p>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted">Posted in: <?php echo htmlspecialchars($thread['forum_id']); ?></small>
                                        <a href="threads.php?forum_id=<?php echo urlencode($thread['forum_id']); ?>#thread-<?php echo $thread['id']; ?>" class="ms-2">View Thread</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center mt-4">
                            <p class="text-muted"><?php echo htmlspecialchars($profile_user['name']); ?> hasn't created any posts yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Replies Tab -->
            <div class="tab-pane fade" id="replies" role="tabpanel">
                <div class="mt-4">
                    <?php if ($user_replies->num_rows > 0): ?>
                        <?php while ($reply = $user_replies->fetch_assoc()): ?>
                            <div class="reply-container clickable-card">
                                <a href="threads.php?forum_id=<?php echo urlencode($reply['forum_id']); ?>#thread-<?php echo $reply['thread_id']; ?>" class="card-link" aria-label="View thread"></a>
                                <div class="clickable-card-content">
                                    <div class="d-flex align-items-start">
                                        <img src="<?php echo htmlspecialchars($profile_user['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 40px;">
                                        <div class="ms-2">
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($profile_user['name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($profile_user['year_level']); ?> Year, Block <?php echo htmlspecialchars($profile_user['block']); ?></small>
                                            <small class="d-block text-muted"><?php echo date('M j, Y \a\t g:i A', strtotime($reply['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="post-content ms-5">
                                        <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted">Replied to: <?php echo htmlspecialchars(substr($reply['thread_description'], 0, 50)) . '...'; ?></small>
                                            <a href="threads.php?forum_id=<?php echo urlencode($reply['forum_id']); ?>#thread-<?php echo $reply['thread_id']; ?>" class="ms-2">View Thread</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center mt-4">
                            <p class="text-muted"><?php echo htmlspecialchars($profile_user['name']); ?> hasn't made any replies yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Resources Tab -->
            <div class="tab-pane fade" id="resources" role="tabpanel">
                <div class="mt-4">
                    <?php if ($user_resources && $user_resources->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($resource = $user_resources->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="resource-card clickable-card">
                                        <a href="download.php?id=<?php echo $resource['id']; ?>" class="card-link" aria-label="Download resource"></a>
                                        <div class="clickable-card-content">
                                            <h5><?php echo htmlspecialchars($resource['title']); ?></h5>
                                            <p class="text-muted"><?php echo htmlspecialchars($resource['description']); ?></p>
                                            <small class="text-muted">Uploaded: <?php echo date('M j, Y', strtotime($resource['created_at'])); ?></small>
                                            <div class="mt-2">
                                                <a href="download.php?id=<?php echo $resource['id']; ?>" class="btn btn-sm btn-primary">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center mt-4">
                            <p class="text-muted"><?php echo htmlspecialchars($profile_user['name']); ?> hasn't uploaded any resources yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>