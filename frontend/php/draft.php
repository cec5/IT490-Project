<?php
include 'header.php';
include 'validation.php';
include 'leagueValidation.php';
require_once 'client_rmq_db.php';

// Fetch unselected players with filters
$request = array();
$request['type'] = 'get_unselected_players';
$request['league_id'] = $leagueId;
$request['filters'] = [
    	'name' => isset($_POST['name']) ? $_POST['name'] : '',
    	'country' => isset($_POST['country']) ? $_POST['country'] : '',
    	'position' => isset($_POST['position']) ? $_POST['position'] : '',
    	'team' => isset($_POST['team']) ? $_POST['team'] : ''
];

$response = createRabbitMQClientDatabase($request);
$players = $response['players'] ?? [];

// Handle Drafting Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['draft_player_id'])) {
    $playerId = intval($_POST['draft_player_id']);
    $draftType = $_POST['draft_type']; // active or reserve

    $draftRequest = array();
    $draftRequest['type'] = 'draft_player';
    $draftRequest['user_id'] = $userId;
    $draftRequest['league_id'] = $leagueId;
    $draftRequest['player_id'] = $playerId;
    $draftRequest['status'] = $draftType;

    $draftResponse = createRabbitMQClientDatabase($draftRequest);

    if ($draftResponse['success']) {
        echo "<script>alert('Player drafted successfully!'); window.location.href = 'draft.php?league_id={$leagueId}';</script>";
    } else {
        echo "<div class='alert alert-danger'>Draft failed: {$draftResponse['message']}</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Players</title>
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Draft Players for <?php echo htmlspecialchars($leagueName); ?></h2>

        <!-- Search and Filter Form -->
        <form method="POST" action="draft.php?league_id=<?php echo $leagueId; ?>" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="name" placeholder="Search by Name">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="country" placeholder="Country">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="position">
                        <option value="">Position</option>
                        <option value="Attacker">Attacker</option>
                        <option value="Midfielder">Midfielder</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="team" placeholder="Team">
                </div>
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>

        <!-- Players Grid -->
        <?php if (!empty($players)): ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($players as $player): ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($player['name']); ?></h5>
                                <p class="card-text">
                                    <strong>Position:</strong> <?php echo htmlspecialchars($player['position']); ?><br>
                                    <strong>Country:</strong> <?php echo htmlspecialchars($player['nationality']); ?><br>
                                    <strong>Team:</strong> <?php echo htmlspecialchars($player['team']); ?>
                                </p>
                                <!-- Draft Buttons -->
                                <form method="POST" action="draft.php?league_id=<?php echo $leagueId; ?>">
                                    <input type="hidden" name="draft_player_id" value="<?php echo $player['id']; ?>">
                                    <button type="submit" name="draft_type" value="active" class="btn btn-success btn-sm">Draft as Active</button>
                                    <button type="submit" name="draft_type" value="reserve" class="btn btn-secondary btn-sm">Draft as Reserve</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No available players matching the criteria.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
