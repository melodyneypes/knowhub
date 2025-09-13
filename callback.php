<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php'; 

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get the secrets from the environment variables
$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']); 
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);

// Get the ID token from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$id_token = $data['id_token'] ?? null;

if (!$id_token) {
    echo json_encode(['success' => false, 'message' => 'No ID token provided']);
    exit();
}

try {
    // Verify the ID token with Google
    $payload = $client->verifyIdToken($id_token);

    if ($payload) {
        // Extract user information
        $email = $payload['email'];
        $name = $payload['name'];
        $picture = $payload['picture'];

        // Determine the user's role based on the email
       $role = null;
        if (strpos($email, '@psu.edu.ph') !== false && strpos($email, 'ac') !== false) {
            $role = 'student';
        } elseif (strpos($email, '@gmail.com') !== false && strpos($email, 'admn') !== false) {
            $role = 'admin';
        } elseif (strpos($email, '@gmail.com') !== false && strpos($email,'ac') !==false) {
            $role = 'instructor';
        } elseif (strpos($email, '@gmail.com') !== false) {
            // Check if this Gmail user is an approved alumni
            $stmt = $conn->prepare("SELECT role FROM users WHERE email = ? AND role = 'alumni'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $role = 'alumni';
            } else {
                echo json_encode(['success' => false, 'message' => 'Access denied: Only PSU accounts and approved alumni can access this system.']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Access denied: Only PSU and Gmail accounts are allowed.']);
            exit();
        }

        // Check if the user already exists in the database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // User does not exist, insert into database
            $stmt = $conn->prepare("INSERT INTO users (email, name, picture, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $name, $picture, $role);
            $stmt->execute();
            
            // Get the newly inserted user ID
            $user_id = $stmt->insert_id;
            $stmt->close();
        } else {
            // User exists, fetch ID
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
        }

        //  Store user info in session
        $_SESSION['user'] = [
            'id' => $user_id,  //  Now the session includes user ID
            'email' => $email,
            'name' => $name,
            'picture' => $picture,
            'role' => $role,
        ];

        // Debugging: Check if session is set (remove after testing)
        // var_dump($_SESSION); die();

        // Redirect based on role
        if ($role === 'student') {
            $redirect_url = 'dashboard-student.php';
        } elseif ($role === 'instructor') {
            $redirect_url = 'dashboard-instructor.php';
        } elseif ($role === 'admin') {
            $redirect_url = 'dashboard-admin.php';
        } else {
            $redirect_url = 'login.php';
        }

        echo json_encode(['success' => true, 'redirect_url' => $redirect_url]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID token']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
