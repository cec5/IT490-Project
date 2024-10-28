<?php
include 'header.php';
include 'validation.php';
require_once 'client_rmq_db.php';

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
                        <a href="league.php?league_id=<?php echo $league['id']; ?>"><?php echo htmlspecialchars($league['name']); ?></a>
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
