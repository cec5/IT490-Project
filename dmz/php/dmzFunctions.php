<?php

// Retrieves Premier League Players, filters and puts them into an array
function getPremierLeaguePlayers() {
	$token = 'bb004732d9244d4a958ce2496f02914f';
    	$premierLeagueTeamsUri = 'https://api.football-data.org/v4/competitions/PL/teams';
    	$headers = ['http' => ['method' => 'GET', 'header' => 'X-Auth-Token: ' . $token]];
    	$context = stream_context_create($headers);

    	// Get all teams in Premier League
    	$response = file_get_contents($premierLeagueTeamsUri, false, $context);
    	$teams = json_decode($response)->teams;

    	$players = [];
    	$requestCount = 0;

   	foreach ($teams as $team) {
        	// Check if we have hit the API limit
        	if ($requestCount >= 10) {
            		// Wait for 60 seconds to avoid exceeding the rate limit
            		sleep(60);
            		$requestCount = 0; // Reset the request count
        	}

        	// Get players for each team
        	$teamUri = 'https://api.football-data.org/v4/teams/' . $team->id;
        	$teamResponse = @file_get_contents($teamUri, false, $context);

        	// Check if request was successful
        	if ($teamResponse === FALSE) {
           		echo "Failed to get data for team ID: " . $team->id . "\n";
            		continue; // Skip to the next team
        	}

        	$squad = json_decode($teamResponse)->squad;

        	if (is_null($squad)) {
            		echo "No squad data for team ID: " . $team->id . "\n";
           		continue;
        	}

        	foreach ($squad as $player) {
            		// Filter by position: check if it includes 'Midfield' or is 'Attacker'
            		if (stripos($player->position, 'midfield') !== false || stripos($player->position, 'wing') !== false || stripos($player->position, 'forward') !== false || stripos($player->position, 'offence') !== false) {
                		$players[] = [
                    			'id' => $player->id,
                   			'name' => $player->name,
                    			'nationality' => $player->nationality,
                    			'position' => $player->position,
                    			'team' => $team->name
                		];
            		}
        	}
        	$requestCount++;
    	}
    	return $players;
}

//var_dump(getPremierLeaguePlayers());
?>
