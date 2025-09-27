<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    header('Location: ../../login.php');
    exit();
}

require '../../db.php';

$user_id = $_SESSION['user']['id'];
$item_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$item_type = isset($_GET['type']) ? $_GET['type'] : null;
$folder_id = isset($_GET['folder']) ? intval($_GET['folder']) : null;

if (!$item_id || !in_array($item_type, ['file', 'folder'])) {
    header('Location: file_manager.php' . ($folder_id ? '?folder=' . $folder_id : ''));
    exit();
}

// Fetch item to verify ownership
if ($item_type === 'folder') {
    $stmt = $conn->prepare("SELECT id FROM file_manager WHERE id = ? AND uploader_id = ? AND item_type = 'folder'");
} else {
    $stmt = $conn->prepare("SELECT file_path FROM file_manager WHERE id = ? AND uploader_id = ? AND item_type = 'file'");
}
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    header('Location: file_manager.php' . ($folder_id ? '?folder=' . $folder_id : ''));
    exit();
}

// Delete the item
if ($item_type === 'file') {
    // Delete file from filesystem
    if (file_exists($item['file_path'])) {
        unlink($item['file_path']);
    }
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM file_manager WHERE id = ? AND uploader_id = ? AND item_type = 'file'");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    // For folders, we need to delete all contents first
    // Delete all files in this folder
    $stmt = $conn->prepare("SELECT file_path FROM file_manager WHERE parent_id = ? AND item_type = 'file'");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($file = $result->fetch_assoc()) {
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
    }
    $stmt->close();
    
    // Delete all items (files and folders) with this parent_id
    $stmt = $conn->prepare("DELETE FROM file_manager WHERE parent_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete the folder itself
    $stmt = $conn->prepare("DELETE FROM file_manager WHERE id = ? AND uploader_id = ? AND item_type = 'folder'");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the file manager
header('Location: file_manager.php' . ($folder_id ? '?folder=' . $folder_id : ''));
exit();
?>