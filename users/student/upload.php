            <?php
            session_start();
            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                die("<div class='alert alert-danger text-center'>Error: User is not logged in.</div>");
            }
            require '../../db.php';
            require 'users/admin/admin_notify.php';
            
            $uploader_id = $_SESSION['user']['id']; // Now it's safe to use
            $timestamp = time() - 300; // 5 minutes ago
            
            // Check upload limit (5 files in last 5 minutes)
            $sql = "SELECT COUNT(*) AS upload_count FROM resources WHERE uploader_id = ? AND created_at > FROM_UNIXTIME(?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $uploader_id, $timestamp);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $upload_count = $row['upload_count'];
            $stmt->close();
            
            if ($upload_count >= 5) {
                $error_message = "<div class='alert alert-warning text-center'>You have reached the upload limit. Please try again later.</div>";
            } else {
                // Handle file upload
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $title = $_POST['title'];
                    $description = $_POST['description'];
                    $subject_id = $_POST['subject_id'];
                    $file = $_FILES['file'];
                    $compress_file = isset($_POST['compress']) && $_POST['compress'] == '1';
            
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        $error_message = "<div class='alert alert-danger'>File upload error: " . $file['error'] . "</div>";
                    } else {
                        // Create upload directory if not exists
                        $upload_dir = 'uploads/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
            
                        $file_path = $upload_dir . basename($file['name']);
                        
                        // If compression is requested, compress the file
                        if ($compress_file) {
                            // Include compression functionality
                            require_once 'compress_file.php';
                            
                            // Create temporary path for original file
                            $temp_path = $upload_dir . 'temp_' . basename($file['name']);
                            
                            // Move uploaded file to temporary location
                            if (!move_uploaded_file($file['tmp_name'], $temp_path)) {
                                $error_message = "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
                            } else {
                                // Compress the file
                                $compressed_path = $upload_dir . 'compressed_' . pathinfo($file['name'], PATHINFO_FILENAME) . '.zip';
                                if (compress_file($temp_path, $compressed_path)) {
                                    // Remove the temporary file
                                    unlink($temp_path);
                                    $file_path = $compressed_path;
                                } else {
                                    // If compression fails, keep the original file
                                    if (!rename($temp_path, $upload_dir . basename($file['name']))) {
                                        $error_message = "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
                                    }
                                }
                            }
                        } else {
                            // Normal file upload without compression
                            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                                $error_message = "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
                            }
                        }
                        
                        // Only proceed if no error occurred
                        if (!isset($error_message)) {
                            // Insert data into resources table
                            $sql = "INSERT INTO resources (title, description, file_path, subject_id, uploader_id) VALUES (?, ?, ?, ?, ?)";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("sssii", $title, $description, $file_path, $subject_id, $uploader_id);
            
                            if ($stmt->execute()) {
                                // Notify admins about the new resource upload
                                notify_admins_new_resource($_SESSION['user']['name'], $title, $_SESSION['user']['id']);
                                
                                header('Location: dashboard-' . $_SESSION['user']['role'] . '.php');
                                exit();
                            } else {
                                $error_message = "<div class='alert alert-danger'>Error inserting data: " . $stmt->error . "</div>";
                            }
                            $stmt->close();
                        }
                    }
                }
            }
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Upload File</title>
                <!-- Include Bootstrap CSS -->
                <link rel="stylesheet" href="assets/bootstrap/bootstrap.min.css">
                <style>
                    body {
                        background-color: #f8f9fa; /* Light background */
                    }
                    .upload-container {
                        max-width: 600px;
                        margin: 50px auto;
                        padding: 20px;
                        background: #ffffff;
                        border-radius: 8px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    .form-header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .form-header h2 {
                        color: #007bff; /* Bootstrap primary color */
                    }
                    .btn-create {
                        margin-bottom: 15px;
                    }
                </style>
            </head>
            <body>
                  <!-- Navbar -->
                <nav class="navbar navbar-light bg-primary shadow-sm">
                    <div class="container">
                        <a class="navbar-brand fw-bold" style="color: white;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
                        <ul class="nav">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard-<?php echo $_SESSION['user']['role']; ?>.php">Home</a>
                            </li>
                            <?php if ($_SESSION['user']['role'] === 'student'): ?>
                            <li>
                                <a class="nav-link" href="profile.php">My Subjects</a>
                            </li>
                            <?php else: ?>
                            <li>
                                <a class="nav-link" href="subjects-handled.php">My Subjects</a>
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
                                <a class="nav-link" href="logout.php">Logout</a>
                            </li>
                            
                        </ul>
                    </div>
                </nav>
                 <a href="uploads.php" class="btn btn-secondary mt-5" style="margin-left: 25px;">BACK</a>
                <div class="container">
                    <div class="upload-container">
                        <div class="form-header">
                            <h2>Upload or Create File</h2>
                            <p class="text-muted">You can upload an existing file or create a new document using OnlyOffice.</p>
                        </div>
                        
                        <?php if (isset($error_message)) echo $error_message; ?>
                        
                        <!-- OnlyOffice Create Document Buttons -->
                        <div class="mb-4">
                            <h5>Create New Document</h5>
                            <div class="d-grid gap-2">
                                <a href="create_document.php?type=docx" class="btn btn-success btn-create">Create Word Document</a>
                                <a href="create_document.php?type=xlsx" class="btn btn-success btn-create">Create Spreadsheet</a>
                                <a href="create_document.php?type=pptx" class="btn btn-success btn-create">Create Presentation</a>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mt-4">Upload Existing File</h5>
                        <form action="upload.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="subject_id" class="form-label">Subject</label>
                                <select name="subject_id" id="subject_id" class="form-select" required>
                                    <option value="" disabled selected>Select a subject</option>
                                    <?php
                                    // Fetch subjects from the database
                                    $sql = "SELECT id, name FROM subjects"; // Assuming your table is named 'subjects'
                                    $result = $conn->query($sql);
            
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No subjects available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="file" class="form-label">File</label>
                                <input type="file" name="file" id="file" class="form-control" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="compress" value="1" class="form-check-input" id="compress">
                                <label class="form-check-label" for="compress">Compress file (ZIP)</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Upload</button>
                        </form>
                    </div>
                </div>
            
                <script>
                document.querySelector('form').addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to upload this file?')) {
                        e.preventDefault();
                    }
                });
                </script>
            </body>
            </html>