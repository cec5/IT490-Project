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

// Fetch the list of all leagues
$request = array();
$request['type'] = 'get_all_leagues';

$leaguesResponse = createRabbitMQClientDatabase($request);
$leagues = isset($leaguesResponse['leagues']) && is_array($leaguesResponse['leagues']) ? $leaguesResponse['leagues'] : [];

// Handle joining a league
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_league_id'])) {
    $leagueIdToJoin = intval($_POST['join_league_id']);

    // Send request to join the league
    $joinRequest = array();
    $joinRequest['type'] = 'join_league';
    $joinRequest['user_id'] = $userId;
    $joinRequest['league_id'] = $leagueIdToJoin;

    $joinResponse = createRabbitMQClientDatabase($joinRequest);

    if ($joinResponse['success']) {
        echo "<script>alert('Successfully joined the league!'); window.location.href = 'leagueslist.php';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to join the league: {$joinResponse['message']}</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Leagues</title>
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">All Leagues</h2>

        <!-- Display the list of leagues -->
        <?php if (!empty($leagues)): ?>
            <ul class="list-group">
                <?php foreach ($leagues as $league): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?php echo htmlspecialchars($league['name']); ?></span>
                        <form action="leagueslist.php" method="POST">
                            <input type="hidden" name="join_league_id" value="<?php echo $league['id']; ?>">
                            <button type="submit" class="btn btn-primary">Join League</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No leagues found.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
