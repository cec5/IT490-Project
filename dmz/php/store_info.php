<?php
require '../../vendor/autoload.php';

use Dotenv\Dotenv;

class StoreData {
    private $apiUrl = "https://api.football-data.org/v4/matches";
    private $apiToken;
    private $db;

    public function __construct() {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        $this->apiToken = $_ENV['API_TOKEN'];

        // Database connection
        $this->db = new mysqli('localhost', 'admin', 'pass', 'project');
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }

    public function storeMatches() {
        $filters = [
            'dateFrom' => '2024-10-01',
            'dateTo' => '2024-10-10',
            'permission' => 'TIER_ONE',
            'competitions' => 'PL'
        ];

        $url = $this->apiUrl . '?' . http_build_query($filters);
        $headers = [
            "X-Auth-Token: {$this->apiToken}"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $matches = json_decode($response, true);

        if (isset($matches['matches'])) {
            foreach ($matches['matches'] as $match) {
                // Format the date to MySQL compatible format
                $date = date('Y-m-d H:i:s', strtotime($match['utcDate']));
                $homeTeam = $match['homeTeam']['name'];
                $awayTeam = $match['awayTeam']['name'];
                $scoreHome = $match['score']['fullTime']['home'];
                $scoreAway = $match['score']['fullTime']['away'];

                // Prepare and execute the insert statement
                $stmt = $this->db->prepare("INSERT INTO matches (date, home_team, away_team, score_home, score_away) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    echo "Prepare failed: " . $this->db->error;
                    continue;
                }
                $stmt->bind_param("sssss", $date, $homeTeam, $awayTeam, $scoreHome, $scoreAway);

                // Check if the insert was successful
                if ($stmt->execute()) {
                    echo "Match stored successfully!<br>";
                } else {
                    echo "Error: " . $stmt->error . "<br>"; // Log the error
                }
                $stmt->close();
            }
            echo "All matches processed!";
        } else {
            echo "No matches found for the specified date range.";
        }
    }
}

// Instantiate the StoreData class and store the matches
$storeData = new StoreData();
$storeData->storeMatches();
?>

