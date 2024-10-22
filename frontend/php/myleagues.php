<?php
// Include necessary files (header, client RabbitMQ)
include 'header.php';
require 'client_rmq_db.php';

// Helper function to get the JWT token from the cookie
function getJwtTokenFromCookie() {
    return isset($_COOKIE['jwt_token']) ? $_COOKIE['jwt_token'] : null;
}

// Check if the user is logged in by checking the JWT token
$token = getJwtTokenFromCookie();

if (!$token) {
    // No token found, redirect to the homepage with a message
    echo "<script>
        alert('You must be logged in to access this page.');
        window.location.href = 'index.php';
    </script>";
    exit();
}

// Validate the token by sending it to the backend
$request = array();
$request['type'] = 'validate_session';
$request['token'] = $token;

$response = createRabbitMQClientDatabase($request);

if (!$response['success']) {
    // Token is invalid or expired, redirect to the homepage
    echo "<script>
        alert('Session expired or invalid. Please log in again.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

// Store the user ID for future use
$userId = $response['userId'];

// Get the list of leagues the user is part of
$request = array();
$request['type'] = 'get_user_leagues';
$request['user_id'] = $userId;

$leaguesResponse = createRabbitMQClientDatabase($request);
$leagues = $leaguesResponse['leagues'];

// If the form for creating a league is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['league_name']) && !empty(trim($_POST['league_name']))) {
        $leagueName = trim($_POST['league_name']);

        // Send request to create a new league
        $createRequest = array();
        $createRequest['type'] = 'create_league';
        $createRequest['user_id'] = $userId;
        $createRequest['league_name'] = $leagueName;

        $createResponse = createRabbitMQClientDatabase($createRequest);

        if ($createResponse['success']) {
            echo "<script>alert('League created successfully!'); window.location.href = 'myleagues.php';</script>";
        } else {
            echo "<div class='alert alert-danger'>Failed to create the league: {$createResponse['message']}</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Please enter a valid league name.</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leagues</title>
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">My Leagues</h2>

        <!-- Display List of Leagues at the top -->
        <?php if (!empty($leagues)): ?>
            <ul class="list-group">
                <?php foreach ($leagues as $league): ?>
                    <li class="list-group-item">
                        <a href="league.php?id=<?php echo $league['id']; ?>"><?php echo htmlspecialchars($league['name']); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You are not part of any leagues yet.</p>
        <?php endif; ?>

        <hr>

        <!-- League Creation Form at the Bottom -->
        <h4>Create a New League</h4>
        <form action="myleagues.php" method="POST">
            <div class="mb-3">
                <label for="leagueName" class="form-label">League Name</label>
                <input type="text" class="form-control" id="leagueName" name="league_name" required>
            </div>
            <button type="submit" class="btn btn-primary">Create League</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

