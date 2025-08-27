<?php
// filepath: e:\CAP101-DANG FILES\archive-system\threads.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Corrected logic to fetch a SINGLE user's details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_user = $stmt->get_result();
$logged_in_user = $result_user->fetch_assoc();
$stmt->close();

// Forum rooms with access control
$forum_rooms = [
    ['id' => 'doit', 'title' => 'DOIT', 'access' => ['student', 'instructor', 'admin']],
    ['id' => 'students', 'title' => 'Students', 'access' => ['student', 'admin']],
    ['id' => 'bsit', 'title' => 'BSIT Department', 'access' => ['student', 'instructor', 'admin', 'alumni']],
    ['id' => 'instructors', 'title' => 'Instructors', 'access' => ['instructor', 'admin']]
];

// Filter forums based on user role
$accessible_forums = array_filter($forum_rooms, function($forum) use ($user_role) {
    return in_array($user_role, $forum['access']);
});

// Check for valid forum_id
$forum_id = $_GET['forum_id'] ?? null;

// If forum_id is not provided or not accessible, redirect to first accessible forum
if ($forum_id === null || !in_array($forum_id, array_column($accessible_forums, 'id'))) {
    $forum_id = reset($accessible_forums)['id'] ?? null;
    
    // If user has no accessible forums, redirect to dashboard
    if ($forum_id === null) {
        header('Location: dashboard-' . ($user_role === 'student' ? 'student' : 'admin') . '.php');
        exit();
    }
}

// Fetch threads for the selected forum, joining with the users table
$stmt = $conn->prepare("SELECT threads.*, users.name as name, users.block, users.year_level, users.picture, users.id as user_id
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
        .nav-links {
            list-style-type: none;
            padding: 0;
            margin-top: 1.5rem;
        }
        .nav-links a {
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            color: #495057;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .nav-links a:hover {
            background-color: #e9ecef;
        }
        .nav-links .active-link a {
            background-color: #007bff;
            color: white;
        }
        .user-link {
            color: inherit;
            text-decoration: none;
        }
        .user-link:hover {
            text-decoration: underline;
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
                    <a class="nav-link active" href="threads.php">Forums</a>
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
    <div class="container-fluid main-container">
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
                        <?php foreach ($accessible_forums as $forum): ?>
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
                                <a href="user_profile.php?user_id=<?php echo $row['user_id']; ?>" class="user-link">
                                    <img src="<?php echo htmlspecialchars($row['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 40px;">
                                </a>
                                <div class="ms-2">
                                    <a href="user_profile.php?user_id=<?php echo $row['user_id']; ?>" class="user-link">
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row['name']); ?></h6>
                                    </a>
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
                                $stmt_replies = $conn->prepare("SELECT replies.*, users.name as name, users.year_level, users.block, users.picture, users.id as user_id
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
                                                <a href="user_profile.php?user_id=<?php echo $reply['user_id']; ?>" class="user-link">
                                                    <img src="<?php echo htmlspecialchars($reply['picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 30px;">
                                                </a>
                                                <div>
                                                    <a href="user_profile.php?user_id=<?php echo $reply['user_id']; ?>" class="user-link">
                                                        <small class="fw-bold"><?php echo htmlspecialchars($reply['name']); ?></small>
                                                    </a>
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