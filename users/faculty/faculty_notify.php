<?php
// faculty_notify.php - Functions to send notifications specifically to faculty users

/**
 * Send a notification to a specific faculty member
 * 
 * @param int $faculty_id The ID of the faculty member to notify
 * @param string $title The notification title
 * @param string $message The notification message
 * @param string $type The type of notification
 * @param int|null $sender_id The ID of the user who triggered the notification
 * @return bool True if successful, false otherwise
 */
function notify_faculty($faculty_id, $title, $message, $type = 'general', $sender_id = null) {
    require_once __DIR__ . '/../../db.php';
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, sender_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isssi", $faculty_id, $title, $message, $type, $sender_id);
    
    if (!$stmt->execute()) {
        error_log("Failed to execute statement: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $stmt->close();
    return true;
}

/**
 * Notify faculty about replies to their forum posts
 * 
 * @param int $faculty_id The ID of the faculty member who created the post
 * @param string $replier_name The name of the user who replied
 * @param string $post_title The title of the post that was replied to
 * @param int|null $replier_id The ID of the replier
 * @return bool True if successful, false otherwise
 */
function notify_faculty_forum_reply($faculty_id, $replier_name, $post_title, $replier_id = null) {
    $title = "New Reply to Your Post";
    $message = $replier_name . " replied to your post: \"" . $post_title . "\"";
    return notify_faculty($faculty_id, $title, $message, 'forum_reply', $replier_id);
}

/**
 * Notify faculty about submissions for activities they have assigned
 * 
 * @param int $faculty_id The ID of the faculty member who assigned the activity
 * @param string $student_name The name of the student who submitted
 * @param string $activity_title The title of the activity
 * @param int|null $student_id The ID of the student
 * @return bool True if successful, false otherwise
 */
function notify_faculty_activity_submission($faculty_id, $student_name, $activity_title, $student_id = null) {
    $title = "New Activity Submission";
    $message = $student_name . " submitted \"" . $activity_title . "\"";
    return notify_faculty($faculty_id, $title, $message, 'activity_submission', $student_id);
}

/**
 * Notify faculty about activity due date reminders
 * 
 * @param int $faculty_id The ID of the faculty member
 * @param string $activity_title The title of the activity
 * @param string $due_date The due date of the activity
 * @return bool True if successful, false otherwise
 */
function notify_faculty_activity_due_date($faculty_id, $activity_title, $due_date) {
    $title = "Activity Due Date Reminder";
    $message = "Activity \"" . $activity_title . "\" is due on " . $due_date;
    return notify_faculty($faculty_id, $title, $message, 'activity_due_date');
}

/**
 * Notify faculty about comments from other instructors on their resources
 * 
 * @param int $faculty_id The ID of the faculty member who uploaded the resource
 * @param string $commenter_name The name of the instructor who commented
 * @param string $resource_title The title of the resource
 * @param int|null $commenter_id The ID of the commenter
 * @return bool True if successful, false otherwise
 */
function notify_faculty_resource_comment($faculty_id, $commenter_name, $resource_title, $commenter_id = null) {
    $title = "New Comment on Your Resource";
    $message = $commenter_name . " commented on your resource: \"" . $resource_title . "\"";
    return notify_faculty($faculty_id, $title, $message, 'resource_comment', $commenter_id);
}

/**
 * Notify faculty about requests to edit their resources
 * 
 * @param int $faculty_id The ID of the faculty member who owns the resource
 * @param string $requester_name The name of the user requesting edit access
 * @param string $resource_title The title of the resource
 * @param int|null $requester_id The ID of the requester
 * @return bool True if successful, false otherwise
 */
function notify_faculty_edit_request($faculty_id, $requester_name, $resource_title, $requester_id = null) {
    $title = "Edit Request for Your Resource";
    $message = $requester_name . " requested to edit your resource: \"" . $resource_title . "\"";
    return notify_faculty($faculty_id, $title, $message, 'edit_request', $requester_id);
}

/**
 * Notify faculty about access requests for their subjects from irregular students
 * 
 * @param int $faculty_id The ID of the faculty member who handles the subject
 * @param string $student_name The name of the student requesting access
 * @param string $subject_name The name of the subject
 * @param int|null $student_id The ID of the student
 * @return bool True if successful, false otherwise
 */
function notify_faculty_access_request($faculty_id, $student_name, $subject_name, $student_id = null) {
    $title = "Access Request for Your Subject";
    $message = $student_name . " requested access to \"" . $subject_name . "\"";
    return notify_faculty($faculty_id, $title, $message, 'access_request', $student_id);
}
?>