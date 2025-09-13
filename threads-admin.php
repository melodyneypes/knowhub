<?php
// filepath: e:\CAP101-DANG FILES\archive-system\threads.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}


require 'db.php';

$user_id = $_SESSION['user']['id'];

// Corrected logic to fetch a SINGLE user's details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_user = $stmt->get_result();
$logged_in_user = $result_user->fetch_assoc();
$stmt->close();

// Static forum rooms array (can be fetched from DB if you prefer)
$forum_rooms = [
    ['id' => 'doit', 'title' => 'DOIT'],
    ['id' => 'students', 'title' => 'Students'],
    ['id' => 'bsit', 'title' => 'BSIT Department'],
    ['id' => 'instructors', 'title' => 'Instructors']
];

// Check for valid forum_id
$forum_id = $_GET['forum_id'] ?? null;
if ($forum_id === null || !in_array($forum_id, array_column($forum_rooms, 'id'))) {
    $forum_id = 'doit';
}

// Fetch threads for the selected forum, joining with the users table
$stmt = $conn->prepare("SELECT threads.*, users.name as name, users.block, users.year_level, users.picture 
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
    <style>
        body {
            background-color: #f0f2f5;
        }
             body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: #fff;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #495057;
            color: #ffc107;
        }
        .profile-img {
            max-width: 120px;
            margin: 20px auto 10px auto;
            display: block;
            border-radius: 50%;
            border: 3px solid #fff;
        }
        .main-container {
            margin-top: 2rem;
        }
        .left-sidebar {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-pic {
            width: 80px;
            height: 80px;
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
        .post-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
        }
        .post-content {
            margin-top: 1rem;
            margin-left: 50px;
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
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3 mx-auto" style="max-width: 70px;">
        <h5 class="text-center mb-4"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard-admin.php"><i class="bi bi-house"></i> Home</a>
            <a class="nav-link" href="profile_settings.php"><i class="bi bi-person"></i> Edit Profile</a>
            <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i> Notifications</a>
            <a class="nav-link active" href="manage_instructors.php"><i class="bi bi-people"></i> Manage Subject Instructors</a>
            <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="admin_user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    <div class="main-content flex-1 col-md-10">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="left-sidebar text-center mb-4">
                    <?php if ($logged_in_user): ?>
                       <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 70px;">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($logged_in_user['name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($logged_in_user['student_number']); ?></p>
                        <p class="text-muted"><?php echo htmlspecialchars($logged_in_user['email']); ?></p>
                        <p class="mt-4">
                            <?php echo htmlspecialchars($logged_in_user['year_level']); ?> Year<br>
                            Block <?php echo htmlspecialchars($logged_in_user['block']); ?><br>
                        </p>
                    <?php else: ?>
                        <p>Please log in to see your profile.</p>
                    <?php endif; ?>
                </div>

                <div class="left-sidebar">
                    <h5 class="fw-bold mb-3">Forum Rooms</h5>
                    <ul class="nav-links">
                        <?php foreach ($forum_rooms as $forum): ?>
                            <li class="<?php echo ($forum['id'] === $forum_id) ? 'active-link' : ''; ?>">
                                <a href="threads.php?forum_id=<?php echo urlencode($forum['id']); ?>">
                                    <?php echo htmlspecialchars($forum['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">/<?php echo htmlspecialchars($forum_id); ?></h2>
                
                <div class="post-container mb-4">
                    <form action="create_thread.php" method="POST">
                        <input type="hidden" name="forum_id" value="<?php echo htmlspecialchars($forum_id); ?>">
                        <div class="d-flex align-items-center">
                             <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 70px;">
                            <textarea name="description" class="form-control" placeholder="Write post here.." rows="3"></textarea>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>

                <?php if ($result_threads->num_rows > 0): ?>
                    <?php while ($row = $result_threads->fetch_assoc()): ?>
                        <div class="post-container" id="thread-<?php echo htmlspecialchars($row['id']); ?>">
                            <div class="d-flex align-items-start">
                                 <img src="<?php echo htmlspecialchars($row['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 40px;">

                                <div class="ms-2">
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row['name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['year_level']); ?> Year, Block <?php echo htmlspecialchars($row['block']); ?></small>
                                </div>
                            </div>
                             <div class="post-content">
                                <p class="view-content"><?php echo htmlspecialchars($row['description']); ?></p>
                                
                                <div class="edit-content d-none">
                                    <textarea class="form-control edit-textarea mb-2" rows="5"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                </div>

                                <div class="post-actions">
                                   <div class="reply-form d-none mt-3">
                                    <form action="create_reply.php" method="POST" class="d-flex">
                                        <input type="hidden" name="thread_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <textarea class="form-control" name="content" placeholder="Write your reply here..." rows="2" required></textarea>
                                        <button type="submit" class="btn btn-primary ms-2">Post</button>
                                    </form>
                                    </div>
                                    <button class="btn btn-sm btn-link reply-button" data-thread-id="<?php echo htmlspecialchars($row['id']); ?>">Reply</button>

                                    <?php if ($row['user_id'] == $_SESSION['user']['id']): ?>
                                    <button class="btn btn-sm edit-button view-link" data-thread-id="<?php echo htmlspecialchars($row['id']); ?>">Edit</button>
                                    <a href="delete_thread.php?id=<?php echo $row['id']; ?>" class="delete-link view-link" onclick="return confirm('Are you sure you want to delete this thread?');">Delete</a>

                                    <button class="btn btn-sm btn-success save-link edit-link d-none" data-thread-id="<?php echo htmlspecialchars($row['id']); ?>">Save</button>
                                    <button class="btn btn-sm btn-secondary cancel-link edit-link d-none">Cancel</button>
                                <?php endif; ?>

                                <?php
                                // Fetch replies for this specific thread
                                $stmt_replies = $conn->prepare("SELECT replies.*, users.name as name, users.year_level, users.block, users.picture
                                                            FROM replies
                                                            INNER JOIN users ON replies.user_id = users.id
                                                            WHERE replies.thread_id = ?
                                                            ORDER BY replies.created_at DESC");
                                $stmt_replies->bind_param("i", $row['id']);
                                $stmt_replies->execute();
                                $result_replies = $stmt_replies->get_result();

                                if ($result_replies->num_rows > 0): ?>
                                    <div class="replies-section mt-4 border-top pt-3">
                                        <?php while ($reply = $result_replies->fetch_assoc()): ?>
                                            <div class="d-flex align-items-start mb-3">
                                             <img src="<?php echo htmlspecialchars($reply['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 30px;">
                                                <div>
                                                    <small class="fw-bold"><?php echo htmlspecialchars($reply['name']); ?></small>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($reply['year_level']); ?> Year, Block <?php echo htmlspecialchars($reply['block']); ?></small>
                                                    <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php endif;
                                $stmt_replies->close();
                                ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center mt-5">
                        <p class="text-muted">No threads yet. Be the first to start a conversation!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
    </div>
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        $(document).on('click', '.edit-button', function() {
            var threadId = $(this).data('thread-id');
            var threadContainer = $('#thread-' + threadId);
            
            // Toggle view/edit content
            threadContainer.find('.view-content').addClass('d-none');
            threadContainer.find('.edit-content').removeClass('d-none');

            // Toggle view/edit links/buttons
            threadContainer.find('.view-link').addClass('d-none');
            threadContainer.find('.edit-link').removeClass('d-none');
        });

        $(document).on('click', '.cancel-link', function() {
            var threadContainer = $(this).closest('.post-container');
            
            // Revert content and links to view mode
            threadContainer.find('.view-content').removeClass('d-none');
            threadContainer.find('.edit-content').addClass('d-none');
            threadContainer.find('.view-link').removeClass('d-none');
            threadContainer.find('.edit-link').addClass('d-none');
        });

        $(document).on('click', '.save-link', function() {
            var threadId = $(this).data('thread-id');
            var threadContainer = $('#thread-' + threadId);
            var newDescription = threadContainer.find('.edit-textarea').val();
            var originalDescription = threadContainer.find('.view-content').text();

            // Use AJAX to save changes without reloading the page
            $.ajax({
                url: 'update_thread.php',
                type: 'POST',
                data: {
                    thread_id: threadId,
                    description: newDescription
                },
                success: function(response) {
                    if (response === "success") {
                        // Update the post content on the page
                        threadContainer.find('.view-content').text(newDescription);
                        
                        // Switch back to view mode
                        threadContainer.find('.view-content').removeClass('d-none');
                        threadContainer.find('.edit-content').addClass('d-none');
                        threadContainer.find('.view-link').removeClass('d-none');
                        threadContainer.find('.edit-link').addClass('d-none');
                    } else {
                        alert("Error saving changes: " + response);
                    }
                },
                error: function() {
                    alert("An error occurred. Please try again.");
                }
            });
        });
        // New JavaScript for replies
        $(document).on('click', '.reply-button', function() {
            var threadId = $(this).data('thread-id');
            var threadContainer = $('#thread-' + threadId);

            // Toggle the visibility of the reply form
            threadContainer.find('.reply-form').toggleClass('d-none');
        });
    </script>
</body>
</html>