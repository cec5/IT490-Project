<?php
include 'header.php';
include 'validation.php';
include 'leagueValidation.php';
require_once 'client_rmq_db.php';

// Fetch user's reserve players (used for proposing trades)
$request = array();
$request['type'] = 'get_user_reserve_players';
$request['user_id'] = $userId;
$request['league_id'] = $leagueId;
$reservePlayersResponse = createRabbitMQClientDatabase($request);
$userReservePlayers = $reservePlayersResponse['reserve_players'] ?? [];

// Fetch reserve players of other league members
$request = array();
$request['type'] = 'get_other_reserve_players';
$request['user_id'] = $userId;
$request['league_id'] = $leagueId;
$otherReservePlayersResponse = createRabbitMQClientDatabase($request);
$otherReservePlayers = $otherReservePlayersResponse['reserve_players'] ?? [];

// Fetch all pending trades where the user is involved
$request = array();
$request['type'] = 'get_pending_trades';
$request['user_id'] = $userId;
$request['league_id'] = $leagueId;
$tradesResponse = createRabbitMQClientDatabase($request);
$pendingTrades = $tradesResponse['trades'] ?? [];

// Handle proposing a trade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['propose_trade'])) {
    	// Assign correctly: proposedPlayerId is the other player's ID; requestedPlayerId is the current user's ID
    	$requestedPlayerId = intval($_POST['proposed_player_id']);
    	$proposedPlayerId = intval($_POST['requested_player_id']);
    	$receivingUserId = intval($_POST['receiving_user_id']);

    	$request = array();
    	$request['type'] = 'propose_trade';
    	$request['proposing_user_id'] = $userId;
    	$request['receiving_user_id'] = $receivingUserId;
    	$request['league_id'] = $leagueId;
    	$request['proposed_player_id'] = $proposedPlayerId;
    	$request['requested_player_id'] = $requestedPlayerId;
    	$proposeTradeResponse = createRabbitMQClientDatabase($request);

    	if ($proposeTradeResponse['success']) {
        	echo "<script>alert('Trade proposed successfully!'); window.location.href = 'trades.php?league_id={$leagueId}';</script>";
    	} else {
        	echo "<div class='alert alert-danger'>Trade proposal failed: {$proposeTradeResponse['message']}</div>";
    	}
}

// Handle accepting, declining, or withdrawing a trade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trade_action'])) {
    $tradeId = intval($_POST['trade_id']);
    $action = $_POST['trade_action'];

    $request = array();
    $request['trade_id'] = $tradeId;

    if ($action === 'accept') {
        $request['type'] = 'accept_trade'; // Use accept_trade for acceptance
    } else {
        $request['type'] = 'update_trade_status';
        $request['status'] = ($action === 'decline') ? 'declined' : 'withdrawn';
    }

    $response = createRabbitMQClientDatabase($request);
    $alertMessage = $response['success'] ? "Trade {$action}ed successfully!" : "Failed to {$action} trade: {$response['message']}";
    echo "<script>alert('$alertMessage'); window.location.href = 'trades.php?league_id={$leagueId}';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trades - <?php echo htmlspecialchars($leagueName); ?></title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Trades in <?php echo htmlspecialchars($leagueName); ?></h2>

    <h4>Available Reserve Players from Other Members</h4>
    <ul class="list-group mb-4">
        <?php foreach ($otherReservePlayers as $player): ?>
            <li class="list-group-item">
                <?php echo htmlspecialchars($player['name']); ?> (<?php echo htmlspecialchars($player['team']); ?> - <?php echo htmlspecialchars($player['position']); ?>)
                <br><small>Owned by: <?php echo htmlspecialchars($player['owner_username']); ?></small>
                <button class="btn btn-primary btn-sm float-end" onclick="openTradeForm(<?php echo $player['player_id']; ?>, <?php echo $player['user_id']; ?>, '<?php echo $player['position']; ?>')">Request Trade</button>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <h4>Pending Trades</h4>
    <ul class="list-group">
        <?php foreach ($pendingTrades as $trade): ?>
            <li class="list-group-item">
                <?php echo htmlspecialchars($trade['proposed_player_name']); ?> for <?php echo htmlspecialchars($trade['requested_player_name']); ?>
                <span class="float-end">
                    <?php if ($trade['proposing_user_id'] == $userId): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="trade_id" value="<?php echo $trade['id']; ?>">
                            <button type="submit" name="trade_action" value="withdraw" class="btn btn-warning btn-sm">Withdraw</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="trade_id" value="<?php echo $trade['id']; ?>">
                            <button type="submit" name="trade_action" value="accept" class="btn btn-success btn-sm">Accept</button>
                            <button type="submit" name="trade_action" value="decline" class="btn btn-danger btn-sm">Decline</button>
                        </form>
                    <?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <!-- Modal for Proposing Trade -->
    <div class="modal fade" id="tradeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Propose Trade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="tradeForm" method="POST">
                        <input type="hidden" name="propose_trade" value="1">
                        <input type="hidden" name="proposed_player_id" id="proposed_player_id">
                        <input type="hidden" name="receiving_user_id" id="receiving_user_id">

                        <div class="mb-3">
                            <label for="requested_player_id" class="form-label">Your Player (Same Position)</label>
                            <select class="form-select" name="requested_player_id" id="requested_player_id" required>
                                <!-- Populate with user’s own reserve players of the same position -->
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Propose Trade</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    function openTradeForm(playerId, userId, position) {
        document.getElementById('proposed_player_id').value = playerId;  // Other player's ID
        document.getElementById('receiving_user_id').value = userId;

        const requestedPlayerSelect = document.getElementById('requested_player_id');
        requestedPlayerSelect.innerHTML = ''; // Clear current options

        <?php foreach ($userReservePlayers as $player): ?>
            if ("<?php echo $player['position']; ?>" === position) {
                const option = document.createElement('option');
                option.value = "<?php echo $player['player_id']; ?>";
                option.textContent = "<?php echo htmlspecialchars($player['name']); ?> (<?php echo htmlspecialchars($player['team']); ?>)";
                requestedPlayerSelect.appendChild(option);
            }
        <?php endforeach; ?>

        new bootstrap.Modal(document.getElementById('tradeModal')).show();
    }
</script>
</body>
</html>
