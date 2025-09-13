<?php
// filepath: e:\CAP101-DANG FILES\archive-system\subject_resources.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
require_once 'db.php';
$user_role = $_SESSION['user']['role'];

// Example static resources array
$resources = [
    'Introduction to Computing' => [
        ['title' => 'CS50’s Introduction to Computer Science', 'url' => 'https://cs50.harvard.edu/x/'],
        ['title' => 'Computer Science Crash Course', 'url' => 'https://www.youtube.com/playlist?list=PL8dPuuaLjXtOfse2ncvffeelTrqvhrz8H'],
    ],
    'Fundamentals of Programming' => [
        ['title' => 'Learn C Programming - Programiz', 'url' => 'https://www.programiz.com/c-programming'],
        ['title' => 'C Programming - W3Schools', 'url' => 'https://www.w3schools.com/c/'],
    ],
    'Intermediate Programming' => [
        ['title' => 'GeeksforGeeks - C Advanced Topics', 'url' => 'https://www.geeksforgeeks.org/c-programming-language/'],
    ],
    'Computer Organization' => [
        ['title' => 'Computer Organization and Architecture', 'url' => 'https://www.tutorialspoint.com/computer_logical_organization/index.htm'],
    ],
    'Discrete Mathematics' => [
        ['title' => 'Discrete Math - MIT OpenCourseWare', 'url' => 'https://ocw.mit.edu/courses/mathematics/18-200-principles-of-discrete-applied-mathematics-fall-2005/'],
    ],
    'Data Structures and Algorithm' => [
        ['title' => 'Data Structures - GeeksforGeeks', 'url' => 'https://www.geeksforgeeks.org/data-structures/'],
    ],
    'Living in the IT Era' => [
        ['title' => 'Living in the IT Era eBook', 'url' => 'https://www.rexestore.com/'],
    ],
    'Human Computer Interaction 1' => [
        ['title' => 'HCI - Interaction Design Foundation', 'url' => 'https://www.interaction-design.org/literature/topics/human-computer-interaction'],
    ],
    'Object-Oriented Programming' => [
        ['title' => 'Java OOP Tutorial', 'url' => 'https://www.w3schools.com/java/java_oop.asp'],
    ],
    'Information Management 1' => [
        ['title' => 'Khan Academy SQL', 'url' => 'https://www.khanacademy.org/computing/computer-programming/sql'],
    ],
    'Human Computer Interaction 2' => [
        ['title' => 'Advanced HCI Topics', 'url' => 'https://www.interaction-design.org/'],
    ],
    'Multimedia Technologies' => [
        ['title' => 'Multimedia - TutorialsPoint', 'url' => 'https://www.tutorialspoint.com/multimedia/index.htm'],
    ],
    'Networking 1' => [
        ['title' => 'Networking Basics - Cisco', 'url' => 'https://skillsforall.com/'],
    ],
    'System Analysis and Design' => [
        ['title' => 'SAD - TutorialsPoint', 'url' => 'https://www.tutorialspoint.com/system_analysis_and_design/index.htm'],
    ],
    'Web Development 1' => [
        ['title' => 'MDN Web Docs', 'url' => 'https://developer.mozilla.org/en-US/docs/Learn'],
    ],
    'Application Development and Emerging Technologies' => [
        ['title' => 'Emerging Tech - IBM', 'url' => 'https://www.ibm.com/cloud/learn/emerging-technologies'],
    ],
    'Information Management 2' => [
        ['title' => 'Advanced SQL - Mode', 'url' => 'https://mode.com/sql-tutorial/'],
    ],
    'Mobile Application Development 1' => [
        ['title' => 'Android Developers Guide', 'url' => 'https://developer.android.com/guide'],
    ],
    'Quantitative Methods' => [
        ['title' => 'Quantitative Analysis - Khan Academy', 'url' => 'https://www.khanacademy.org/math/statistics-probability'],
    ],
    'Networking 2' => [
        ['title' => 'Advanced Networking - GeeksforGeeks', 'url' => 'https://www.geeksforgeeks.org/computer-network-tutorials/'],
    ],
    'Operating Systems' => [
        ['title' => 'Operating Systems - Neso Academy', 'url' => 'https://www.youtube.com/playlist?list=PLBlnK6fEyqRgLLlzdgiTUKULKJPYyT2t-'],
    ],
    'Web System and Technologies 1' => [
        ['title' => 'Full Stack Web Dev - freeCodeCamp', 'url' => 'https://www.freecodecamp.org/'],
    ],
    'Capstone Project 1' => [
        ['title' => 'Capstone Guide - Coursera', 'url' => 'https://www.coursera.org/projects'],
    ],
    'Elective 1' => [
        ['title' => 'Advanced Web Technologies - MDN', 'url' => 'https://developer.mozilla.org/en-US/'],
    ],
    'Elective 2' => [
        ['title' => 'Advanced Android Topics', 'url' => 'https://developer.android.com/'],
    ],
    'Information Assurance and Security 1' => [
        ['title' => 'Cybersecurity Fundamentals - Cybrary', 'url' => 'https://www.cybrary.it/'],
    ],
    'Integrated Programming and Technologies' => [
        ['title' => 'Software Integration Techniques', 'url' => 'https://www.softwaretestinghelp.com/system-integration-testing/'],
    ],
    'Technopreneurship' => [
        ['title' => 'Startup School - Y Combinator', 'url' => 'https://www.startupschool.org/'],
    ],
    'Capstone Project 2' => [
        ['title' => 'Final Year Projects - IEEE', 'url' => 'https://ieeexplore.ieee.org/'],
    ],
    'Elective 3' => [
        ['title' => 'Modern Web & Mobile Topics', 'url' => 'https://web.dev/'],
    ],
    'Elective 4' => [
        ['title' => 'Web & Mobile Advanced Projects', 'url' => 'https://developer.apple.com/'],
    ],
    'Information Assurance and Security 2' => [
        ['title' => 'Security Best Practices - OWASP', 'url' => 'https://owasp.org/'],
    ],
    'System Administration and Maintenance' => [
        ['title' => 'Linux Sysadmin Guide', 'url' => 'https://linuxjourney.com/'],
    ],
    'System Integration and Architecture' => [
        ['title' => 'Systems Architecture - IBM', 'url' => 'https://www.ibm.com/cloud/architecture'],
    ],
];

// Handle search
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$filtered_resources = [];
if ($search !== '') {
    foreach ($resources as $subject => $links) {
        if (stripos($subject, $search) !== false) {
            $filtered_resources[$subject] = $links;
        } else {
            // Also search in link titles
            $matched_links = [];
            foreach ($links as $link) {
                if (stripos($link['title'], $search) !== false) {
                    $matched_links[] = $link;
                }
            }
            if (!empty($matched_links)) {
                $filtered_resources[$subject] = $matched_links;
            }
        }
    }
} else {
    $filtered_resources = $resources;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Subject Resources</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
   <nav class="navbar navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
            <ul class="nav">
               <a class="nav-link" href="dashboard-<?php echo $user_role; ?>.php">Home</a>
                <?php if ($user_role === 'student'): ?>
                <li>
                    <a class="nav-link" href="dashboard-student.php">My Subjects</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="browse.php">Browse</a>
                </li>
                <?php endif; ?>

                <?php if ($user_role === 'instructor'): ?>
                 <li>
                    <a class="nav-link" href="subjects-handled.php">My Handled Subjects</a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link active" href="threads.php">Forums</a>
                </li>

                 <?php if ($user_role === 'student'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="external.php">External Resources</a>
                </li> 
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="external-instructor.php">External Resources</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
                </li>
                
            </ul>
        </div>
    </nav>
<div class="container mt-5">
    <h2>External Resources Per Subject</h2>
    <form method="GET" action="external.php" class="mb-5">
        <div class="input-group mb-3">
            <input type="text" name="search" class="form-control" placeholder="Search resources..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button class="btn btn-primary" type="submit">Search</button>
            <button class="btn btn-secondary" type="reset" onclick="window.location.href='external.php';">Reset</button>
        </div>
    </form>
  <?php if (empty($filtered_resources)): ?>
    <div class="alert alert-warning">No subjects or resources found.</div>
<?php else: ?>
    <?php foreach ($filtered_resources as $subject => $links): ?>
        <div class="card mb-4">
            <div class="card-header fw-bold bg-primary text-white"><?php echo htmlspecialchars($subject); ?></div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($links as $link): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($link['title']); ?></h5>
                                    <p class="card-text text-muted">External Resource</p>
                                    <div class="mt-auto">
                                        <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                                           target="_blank" 
                                           class="btn btn-primary w-100">
                                            Visit Resource
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</body>
</html>
