<?php
// notify.php - Notification functions for the system

/**
 * Send a general notification to a user
 * 
 * @param int $user_id The ID of the user to notify
 * @param string $title The notification title
 * @param string $message The notification message
 * @param string $type The type of notification (default: 'general')
 * @param int|null $sender_id The ID of the user who triggered the notification
 * @return bool True if successful, false otherwise
 */
function send_notification($user_id, $title, $message, $type = 'general', $sender_id = null) {
    require 'db.php';
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, sender_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isssi", $user_id, $title, $message, $type, $sender_id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Notify when a file is downloaded
 * 
 * @param int $uploader_id The ID of the user who uploaded the file
 * @param string $downloader_name The name of the user who downloaded the file
 * @param string $file_name The name of the downloaded file
 * @param int|null $downloader_id The ID of the downloader (if available)
 * @return bool True if successful, false otherwise
 */
function notify_file_download($uploader_id, $downloader_name, $file_name, $downloader_id = null) {
    $title = "File Downloaded";
    $message = $downloader_name . " downloaded your file: " . $file_name;
    return send_notification($uploader_id, $title, $message, 'download', $downloader_id);
}

/**
 * Notify when there's a reply to a post
 * 
 * @param int $post_author_id The ID of the user who authored the original post
 * @param string $replier_name The name of the user who replied
 * @param string $post_title The title of the post that was replied to
 * @param int|null $replier_id The ID of the user who replied (if available)
 * @return bool True if successful, false otherwise
 */
function notify_post_reply($post_author_id, $replier_name, $post_title, $replier_id = null) {
    $title = "New Reply to Your Post";
    $message = $replier_name . " replied to your post: " . $post_title;
    return send_notification($post_author_id, $title, $message, 'reply', $replier_id);
}

/**
 * Notify instructors about edit requests
 * 
 * @param int $instructor_id The ID of the instructor
 * @param string $requester_name The name of the user requesting the edit
 * @param string $resource_name The name of the resource to be edited
 * @param int|null $requester_id The ID of the requester (if available)
 * @return bool True if successful, false otherwise
 */
function notify_edit_request($instructor_id, $requester_name, $resource_name, $requester_id = null) {
    $title = "Edit Request";
    $message = $requester_name . " requested to edit: " . $resource_name;
    return send_notification($instructor_id, $title, $message, 'edit_request', $requester_id);
}

/**
 * Notify instructors about forum posts in their rooms
 * 
 * @param int $instructor_id The ID of the instructor
 * @param string $poster_name The name of the user who posted
 * @param string $forum_name The name of the forum where the post was made
 * @param int|null $poster_id The ID of the user who posted (if available)
 * @return bool True if successful, false otherwise
 */
function notify_instructor_forum_post($instructor_id, $poster_name, $forum_name, $poster_id = null) {
    $title = "New Forum Post";
    $message = $poster_name . " posted in your forum room: " . $forum_name;
    return send_notification($instructor_id, $title, $message, 'forum', $poster_id);
}

/**
 * Notify when a user's edit request is approved
 * 
 * @param int $user_id The ID of the user whose request was approved
 * @param string $resource_name The name of the resource that was approved for editing
 * @param int|null $approver_id The ID of the approver (if available)
 * @return bool True if successful, false otherwise
 */
function notify_edit_approved($user_id, $resource_name, $approver_id = null) {
    $title = "Edit Request Approved";
    $message = "Your request to edit '" . $resource_name . "' has been approved.";
    return send_notification($user_id, $title, $message, 'edit_approved', $approver_id);
}

/**
 * Notify when a user's edit request is declined
 * 
 * @param int $user_id The ID of the user whose request was declined
 * @param string $resource_name The name of the resource that was declined for editing
 * @param int|null $decliner_id The ID of the decliner (if available)
 * @return bool True if successful, false otherwise
 */
function notify_edit_declined($user_id, $resource_name, $decliner_id = null) {
    $title = "Edit Request Declined";
    $message = "Your request to edit '" . $resource_name . "' has been declined.";
    return send_notification($user_id, $title, $message, 'edit_declined', $decliner_id);
}

/**
 * Notify when a new resource is uploaded to a subject
 * 
 * @param int $instructor_id The ID of the instructor
 * @param string $uploader_name The name of the user who uploaded
 * @param string $resource_name The name of the uploaded resource
 * @param string $subject_name The name of the subject
 * @param int|null $uploader_id The ID of the uploader (if available)
 * @return bool True if successful, false otherwise
 */
function notify_new_resource($instructor_id, $uploader_name, $resource_name, $subject_name, $uploader_id = null) {
    $title = "New Resource Uploaded";
    $message = $uploader_name . " uploaded a new resource '" . $resource_name . "' to " . $subject_name;
    return send_notification($instructor_id, $title, $message, 'new_resource', $uploader_id);
}

/**
 * Mark a notification as read
 * 
 * @param int $notification_id The ID of the notification
 * @param int $user_id The ID of the user (for security check)
 * @return bool True if successful, false otherwise
 */
function mark_notification_read($notification_id, $user_id) {
    require 'db.php';
    
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int $user_id The ID of the user
 * @return bool True if successful, false otherwise
 */
function mark_all_notifications_read($user_id) {
    require 'db.php';
    
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $user_id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get unread notifications count for a user
 * 
 * @param int $user_id The ID of the user
 * @return int Number of unread notifications
 */
function get_unread_notifications_count($user_id) {
    require 'db.php';
    
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return 0;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    
    return $row['count'];
}

/**
 * Get notifications for a user
 * 
 * @param int $user_id The ID of the user
 * @param int $limit Number of notifications to retrieve (default: 10)
 * @return array Array of notifications
 */
function get_user_notifications($user_id, $limit = 10) {
    require 'db.php';
    
    $notifications = [];
    
    $sql = "SELECT n.*, u.name as sender_name 
            FROM notifications n 
            LEFT JOIN users u ON n.sender_id = u.id 
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return $notifications;
    }
    
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    
    return $notifications;
}
?>