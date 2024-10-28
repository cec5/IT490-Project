<?php
include 'header.php';
include 'validation.php';
include 'leagueValidation.php';
require_once 'client_rmq_db.php';

// Fetch the user's roster in this league
$request = array();
$request['type'] = 'get_user_roster';
$request['user_id'] = $userId;
$request['league_id'] = $leagueId;

$rosterResponse = createRabbitMQClientDatabase($request);
$roster = $rosterResponse['roster'] ?? ['attackers' => [], 'midfielders' => []];

// Handle swap and remove requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    	if (isset($_POST['swap_player_id']) && isset($_POST['swap_with_id'])) {
        	// Swap active and reserve players
        	$swapRequest = [
            		'type' => 'swap_players',
            		'user_id' => $userId,
            		'league_id' => $leagueId,
            		'active_player_id' => intval($_POST['swap_player_id']),
            		'reserve_player_id' => intval($_POST['swap_with_id'])
        	];
        	$swapResponse = createRabbitMQClientDatabase($swapRequest);
        
       	 	if ($swapResponse['success']) {
            		echo "<script>alert('Players swapped successfully!'); window.location.href = 'myroster.php?league_id={$leagueId}';</script>";
        	} else {
            		echo "<div class='alert alert-danger'>Failed to swap players: {$swapResponse['message']}</div>";
        	}
    	} elseif (isset($_POST['promote_reserve_id'])) {
        	// Promote a reserve to active
       		$promoteRequest = [
            		'type' => 'promote_reserve',
            		'user_id' => $userId,
            		'league_id' => $leagueId,
            		'reserve_player_id' => intval($_POST['promote_reserve_id'])
        	];
        	$promoteResponse = createRabbitMQClientDatabase($promoteRequest);

        	if ($promoteResponse['success']) {
            		echo "<script>alert('Reserve player promoted to active!'); window.location.href = 'myroster.php?league_id={$leagueId}';</script>";
        	} else {
            		echo "<div class='alert alert-danger'>Promotion failed: {$promoteResponse['message']}</div>";
        	}
    	} elseif (isset($_POST['remove_reserve_id'])) {
        	// Remove a reserve player from the roster
        	$removeRequest = [
            		'type' => 'remove_reserve',
            		'user_id' => $userId,
            		'league_id' => $leagueId,
            		'reserve_player_id' => intval($_POST['remove_reserve_id'])
        	];
        	$removeResponse = createRabbitMQClientDatabase($removeRequest);

        	if ($removeResponse['success']) {
            		echo "<script>alert('Player removed successfully!'); window.location.href = 'myroster.php?league_id={$leagueId}';</script>";
        	} else {
            		echo "<div class='alert alert-danger'>Failed to remove player: {$removeResponse['message']}</div>";
        	}
    	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Roster - <?php echo htmlspecialchars($leagueName); ?></title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">My Roster for <?php echo htmlspecialchars($leagueName); ?></h2>

    <!-- Tabs for Attackers and Midfielders -->
    <ul class="nav nav-tabs" id="rosterTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="attackers-tab" data-bs-toggle="tab" href="#attackers" role="tab">Attackers</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="midfielders-tab" data-bs-toggle="tab" href="#midfielders" role="tab">Midfielders</a>
        </li>
    </ul>

    <div class="tab-content mt-4" id="rosterTabContent">
        <!-- Attackers Tab -->
        <div class="tab-pane fade show active" id="attackers" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <h4>Active Attackers</h4>
                    <ul class="list-group">
                        <?php foreach ($roster['attackers']['active'] as $player): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($player['name']); ?> (<?php echo htmlspecialchars($player['team']); ?>)
                                <form method="POST" class="d-flex align-items-center">
                                    <select name="swap_with_id" class="form-select form-select-sm me-2" required>
                                        <option value="">Swap with...</option>
                                        <?php foreach ($roster['attackers']['reserve'] as $reservePlayer): ?>
                                            <option value="<?php echo $reservePlayer['id']; ?>"><?php echo htmlspecialchars($reservePlayer['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="swap_player_id" value="<?php echo $player['id']; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">Swap</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4>Reserve Attackers</h4>
                    <ul class="list-group">
                        <?php foreach ($roster['attackers']['reserve'] as $player): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($player['name']); ?> (<?php echo htmlspecialchars($player['team']); ?>)
                                <div>
                                    <?php if (count($roster['attackers']['active']) < 4): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="promote_reserve_id" value="<?php echo $player['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">Promote</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="remove_reserve_id" value="<?php echo $player['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Midfielders Tab -->
        <div class="tab-pane fade" id="midfielders" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <h4>Active Midfielders</h4>
                    <ul class="list-group">
                        <?php foreach ($roster['midfielders']['active'] as $player): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($player['name']); ?> (<?php echo htmlspecialchars($player['team']); ?>)
                                <form method="POST" class="d-flex align-items-center">
                                    <select name="swap_with_id" class="form-select form-select-sm me-2" required>
                                        <option value="">Swap with...</option>
                                        <?php foreach ($roster['midfielders']['reserve'] as $reservePlayer): ?>
                                            <option value="<?php echo $reservePlayer['id']; ?>"><?php echo htmlspecialchars($reservePlayer['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="swap_player_id" value="<?php echo $player['id']; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">Swap</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4>Reserve Midfielders</h4>
                    <ul class="list-group">
                        <?php foreach ($roster['midfielders']['reserve'] as $player): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($player['name']); ?> (<?php echo htmlspecialchars($player['team']); ?>)
                                <div>
                                    <?php if (count($roster['midfielders']['active']) < 4): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="promote_reserve_id" value="<?php echo $player['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">Promote</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="remove_reserve_id" value="<?php echo $player['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
