<?php
session_start();

// Example check (customize this for your auth system)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guest') {
    header("Location: ../../index.php");
    exit;
}
$user = $_SESSION['user'];
require_once '../../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Dashboard | KnowHub</title>
   <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #fff;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 0;
            transition: background 0.2s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #f1f3f5;
            color: #007bff;
        }
        .sidebar .user-profile {
            position: absolute;
            bottom: 1rem;
            left: 0;
            right: 0;
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            text-align: center;
        }
        .sidebar .user-profile .avatar {
            background-color: #0d6efd;
            color: #fff;
            width: 50px;
            height: 50px;
            line-height: 50px;
            border-radius: 50%;
            display: inline-block;
            font-size: 1.5rem;
        }
        .main-content {
            padding: 2rem;
        }
        .card-custom {
            border: 1px solid #e9ecef;
            border-radius: 16px;
            padding: 1.5rem;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            transition: transform 0.2s;
        }
        .card-custom:hover {
            transform: translateY(-2px);
        }
        .card-custom .icon {
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0 sidebar position-relative">
            <div class="p-3">
                <h4 class="fw-bold mb-4">KnowHub</h4>
                <nav class="nav flex-column">
                    <a href="dashboard-guests.php" class="nav-link active"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                    <a href="threads-guests.php" class="nav-link"><i class="bi bi-chat-left-text me-2"></i> Forums</a>
                    <a href="#" class="nav-link"><i class="bi bi-collection me-2"></i> Browse Resources</a>
                    <a href="#" class="nav-link"><i class="bi bi-cloud-arrow-up me-2"></i> My Uploads</a>
                    <a href="../../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                </nav>
            </div>

            <!-- Guest Profile -->
            <div class="user-profile">
                <div class="avatar mb-2">
                    <i class="bi bi-person"></i>
                </div>
                <div class="fw-semibold"><?php echo htmlspecialchars($user['name'] ?? 'Guest'); ?></div>
                <small class="text-muted">Guest</small>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 main-content">
            <h2 class="fw-bold">Welcome Back, Guest!</h2>
            <p class="text-muted">Explore community discussions, open resources, and contribute insights.</p>

            <h5 class="fw-semibold mt-4 mb-3">Your Dashboard at a Glance</h5>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card-custom text-center">
                        <div class="icon text-primary"><i class="bi bi-chat-left-dots"></i></div>
                        <h6 class="fw-bold">Community Discussions</h6>
                        <p class="text-muted small mb-3">Join and share your thoughts with other learners and educators.</p>
                        <a href="threads-guests.php" class="btn btn-outline-primary btn-sm">View Forums</a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-custom text-center">
                        <div class="icon text-success"><i class="bi bi-folder-symlink"></i></div>
                        <h6 class="fw-bold">Open Learning Resources</h6>
                        <p class="text-muted small mb-3">Access community-shared materials, articles, and presentations.</p>
                        <a href="#" class="btn btn-outline-success btn-sm">Browse Resources</a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-custom text-center">
                        <div class="icon text-info"><i class="bi bi-upload"></i></div>
                        <h6 class="fw-bold">Contribute Content</h6>
                        <p class="text-muted small mb-3">Share your knowledge by uploading useful materials or guides.</p>
                        <a href="#" class="btn btn-info btn-sm text-white">Upload Material</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
