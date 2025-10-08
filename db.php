<?php
// Check if we're on Heroku (using Heroku's DATABASE_URL) or local development
if (getenv('DATABASE_URL')) {
    // Heroku deployment
    $dbopts = parse_url(getenv('DATABASE_URL'));
    $host = $dbopts["host"];
    $db = ltrim($dbopts["path"], '/');
    $user = $dbopts["user"];
    $pass = $dbopts["pass"];
    $port = $dbopts["port"];
    
    $conn = new mysqli($host, $user, $pass, $db, $port);
} else {
    // Local development
    $host = 'localhost';
    $db = 'archive2'; // Xampp database name
    $user = 'root';
    $pass = '';
    
    $conn = new mysqli($host, $user, $pass, $db);
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>