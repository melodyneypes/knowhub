<?php
// admin_notify.php - Functions to send notifications specifically to admin users

/**
 * Send a notification to all admin users
 * 
 * @param string $title The notification title
 * @param string $message The notification message
 * @param string $type The type of notification
 * @param int|null $sender_id The ID of the user who triggered the notification
 * @return bool True if successful, false otherwise
 */
function notify_admins($title, $message, $type = 'general', $sender_id = null) {
    require_once __DIR__ . '/../../db.php';
    
    // Get all admin users
    $admin_sql = "SELECT id FROM users WHERE role = 'admin'";
    $admin_result = $conn->query($admin_sql);
    
    if (!$admin_result) {
        error_log("Failed to fetch admin users: " . $conn->error);
        return false;
    }
    
    $success = true;
    
    // Send notification to each admin
    while ($admin = $admin_result->fetch_assoc()) {
        $sql = "INSERT INTO notifications (user_id, title, message, type, sender_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $conn->error);
            $success = false;
            continue;
        }
        
        $stmt->bind_param("isssi", $admin['id'], $title, $message, $type, $sender_id);
        
        if (!$stmt->execute()) {
            error_log("Failed to execute statement: " . $stmt->error);
            $success = false;
        }
        
        $stmt->close();
    }
    
    return $success;
}

/**
 * Notify admins about new resource uploads
 * 
 * @param string $uploader_name The name of the user who uploaded the resource
 * @param string $resource_title The title of the uploaded resource
 * @param int|null $uploader_id The ID of the uploader
 * @return bool True if successful, false otherwise
 */
function notify_admins_new_resource($uploader_name, $resource_title, $uploader_id = null) {
    $title = "New Resource Uploaded";
    $message = $uploader_name . " uploaded a new resource: " . $resource_title;
    return notify_admins($title, $message, 'resource_upload', $uploader_id);
}

/**
 * Notify admins about new forum posts
 * 
 * @param string $poster_name The name of the user who posted
 * @param string $post_title The title of the new post
 * @param string $forum_name The name of the forum
 * @param int|null $poster_id The ID of the poster
 * @return bool True if successful, false otherwise
 */
function notify_admins_new_forum_post($poster_name, $post_title, $forum_name, $poster_id = null) {
    $title = "New Forum Post";
    $message = $poster_name . " created a new post in " . $forum_name . ": " . $post_title;
    return notify_admins($title, $message, 'forum_post', $poster_id);
}

/**
 * Notify admins about replies to forum posts
 * 
 * @param string $replier_name The name of the user who replied
 * @param string $post_title The title of the post that was replied to
 * @param string $forum_name The name of the forum
 * @param int|null $replier_id The ID of the replier
 * @return bool True if successful, false otherwise
 */
function notify_admins_forum_reply($replier_name, $post_title, $forum_name, $replier_id = null) {
    $title = "New Forum Reply";
    $message = $replier_name . " replied to post \"" . $post_title . "\" in " . $forum_name;
    return notify_admins($title, $message, 'forum_reply', $replier_id);
}
?>