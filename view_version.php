<?php
// view_version.php

// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
// Include your database connection and config
require 'db.php'; 

// --- Simulation for Authentication/Access ---
if (!isset($_SESSION['user'])) {
    // Simulate user access for template viewing
    $_SESSION['user'] = ['id' => 1, 'role' => 'admin']; 
}

// 1. Input Validation and Sanitization
$file_path = filter_input(INPUT_GET, 'file_path', FILTER_SANITIZE_URL);

if (!$file_path) {
    die("Error: No file path provided.");
}

// SECURITY CHECK: Ensure the path is within the allowed 'uploads' directory
if (strpos($file_path, 'uploads/') !== 0) {
    die("Access denied: Invalid file path structure.");
}
// ---------------------------------------------

// 2. Determine File Type and Viewing Path
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$file_name = basename($file_path);
$viewing_path = $file_path;
$is_viewable = false;

// Check if the file is natively viewable (PDF or image)
if (in_array($file_extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif'])) {
    $is_viewable = true;
} 
// Check for DOCX/DOC and attempt a conversion path
elseif (in_array($file_extension, ['docx', 'doc', 'pptx', 'xlsx'])) {
    
    // -------------------------------------------------------------------------
    // !!! IMPORTANT: REAL-WORLD CONVERSION LOGIC GOES HERE !!!
    // -------------------------------------------------------------------------
    
    // In a real application, you would check for an already converted PDF version.
    $pdf_file_path = str_replace("." . $file_extension, ".pdf", $file_path);
    
    // Example: $pdf_file_path could be 'uploads/MyDocument.docx' converted to 'uploads/MyDocument.pdf'
    
    // If the PDF doesn't exist, you'd trigger a server script here:
    // if (!file_exists($pdf_file_path)) {
    //     // 1. Call your LibreOffice/Unoconv/PHPOffice converter script
    //     // 2. Wait for conversion to complete and check success
    // }
    
    // Assume the converted file path is the target for the viewer
    // NOTE: For this template, we assume the conversion target *should* be a PDF.
    $viewing_path = $pdf_file_path; 
    $is_viewable = true; // We assume the conversion will succeed and produce a PDF.
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Viewing Version: <?php echo htmlspecialchars($file_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* (CSS styles remain the same) */
        body {
            background-color: #f8f9fa;
        }
        .pdf-container {
            width: 100%;
            height: calc(100vh - 150px); /* Full height minus header/footer space */
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <nav class="navbar navbar-light bg-white border-bottom shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1 mx-auto">
                Viewing: <?php echo htmlspecialchars($file_name); ?>
            </span>
            <a href="<?php echo htmlspecialchars($file_path); ?>" download class="btn btn-primary me-2">
                Download Original File
            </a>
        </div>
    </nav>

    <div class="p-4">
        <?php if ($is_viewable): ?>
            <div class="pdf-container">
                <iframe src="<?php echo htmlspecialchars($viewing_path); ?>#toolbar=0" title="Document Viewer for <?php echo htmlspecialchars($file_name); ?>">
                    <p>It looks like your browser doesn't support PDF embedding. <a href="<?php echo htmlspecialchars($viewing_path); ?>">Click here to download the file.</a></p>
                </iframe>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">
                <p class="h4">File Type Not Viewable</p>
                <p>The file format (**<?php echo strtoupper($file_extension); ?>**) cannot be displayed directly in the browser. Please use the **Download Original File** button to view it.</p>
            </div>
        <?php endif; ?>
        
        <div class="mt-3 text-center text-muted small">
            Note: Non-PDF files are attempted to be converted to PDF for in-browser viewing.
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>