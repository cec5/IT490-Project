<?php
require '../vendor/autoload.php';

use Dotenv\Dotenv;

class SoccerData {
    private $apiUrl = "https://api.football-data.org/v4/matches";
    private $apiToken;

    public function __construct() {
    	$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
	$dotenv->load();
	$this->apiToken = $_ENV['API_TOKEN'];
    }


    public function getMatches() {
        $filters = [
            'dateFrom' => '2024-10-22',
            'dateTo' => '2024-10-31',
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

        error_log(print_r(json_decode($response, true), true));

        return json_decode($response, true);
    }
}

$soccerData = new SoccerData();
$matches = $soccerData->getMatches();
print_r($matches);
?>
