<?php
require_once 'client_rmq_db.php';

$leagueId = isset($_GET['league_id']) ? intval($_GET['league_id']) : 0;

if (!$leagueId) {
    	// No league ID found, redirect to the homepage
    	echo "<script>
        	alert('Invalid league.');
        	window.location.href = 'index.php';
    	</script>";
    	exit();
}

// Fetch the league name
$request = array();
$request['type'] = 'get_league_name';
$request['league_id'] = $leagueId;

$leagueNameResponse = createRabbitMQClientDatabase($request);

if (!$leagueNameResponse['success']) {
    	// League name not found, redirect to the homepage
    	echo "<script>
        	alert('League not found.');
        	window.location.href = 'index.php';
    	</script>";
    	exit();
}

// Store the league name
$leagueName = $leagueNameResponse['league_name'];

// Check if the user is part of this league
$request = array();
$request['type'] = 'validate_league_access';
$request['user_id'] = $userId;
$request['league_id'] = $leagueId;

$accessResponse = createRabbitMQClientDatabase($request);

if (!$accessResponse['success']) {
    	// User is not part of this league, deny access
    	echo "<script>
        	alert('You are not a member of this league.');
        	window.location.href = 'myleagues.php';
    	</script>";
    	exit();
}
?>
