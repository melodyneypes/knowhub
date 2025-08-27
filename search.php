<?php
session_start();
require 'db.php';

$search_query = $_GET['query'];
$sql = "SELECT * FROM resources WHERE title LIKE ? OR description LIKE ?";
$stmt = $conn->prepare($sql);
$search_term = '%' . $search_query . '%';
$stmt->bind_param("ss", $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();
$resources = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Search Results</h2>
        <div class="row">
            <?php foreach ($resources as $resource): ?>
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $resource['title']; ?></h5>
                        <p class="card-text"><?php echo $resource['description']; ?></p>
                        <a href="<?php echo $resource['file_path']; ?>" class="btn btn-primary">Download</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>