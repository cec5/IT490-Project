<?php
require 'databaseFunctions.php';

// GET ALL LEAGUES AND LEAGUE IDs
function getLeagues() {
        $db = dbConnect();

	$query = "SELECT id, name FROM leagues";
	$stmt  = $db->query($query);

        if (!$stmt) {
                $db->close();
                return array("success" => false, "message" => "Database error: Unable to fetch leagues.");
        }

        $leagues = [];
        while ($row = $stmt->fetch_assoc()) {
                $leagues[] = $row;
        }

        $db->close();
        return array("success" => true, "data" => $leagues);
}

// GET ALL USER IDs FROM EACH RESPECTIVE LEAGUE ID
function getUsersByLeague($leagueId) {
    $db = dbConnect();

    $query = "SELECT user_id FROM user_league WHERE league_id = ?";
    $stmt = $db->prepare($query);

    if (!$stmt) {
        $db->close();
        return array("success" => false, "message" => "Database error: Unable to prepare statement.");
    }

    $stmt->bind_param("i", $leagueId);
    $stmt->execute();
    $result = $stmt->get_result();

    $userIds = array();
    while ($row = $result->fetch_assoc()) {
        $userIds[] = $row['user_id'];
    }

    $stmt->close();
    $db->close();

    if (count($userIds) > 0) {
        return array("success" => true, "data" => $userIds);
    } else {
        return array("success" => false, "message" => "No users found for league ID $leagueId.");
    }
}

// GET EMAILS BASED ON USER IDs
function getUserEmails($userIds) {
    $db = dbConnect();

    if (empty($userIds)) {
        return array("success" => false, "message" => "No user IDs provided.");
    }

    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $query = "SELECT email FROM users WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);

    if (!$stmt) {
        $db->close();
        return array("success" => false, "message" => "Database error: Unable to prepare statement.");
    }

    $types = str_repeat('i', count($userIds));
    $stmt->bind_param($types, ...$userIds);
    $stmt->execute();
    $result = $stmt->get_result();

    $emails = [];
    while ($row = $result->fetch_assoc()) {
	    $emails[] = $row['email'];
    }

    $stmt->close();
    $db->close();
    return array("success" => true, "data" => $emails);
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sysint8@gmail.com'; // common email
    $mail->Password = 'pwsy qqop zbud iqdn'; // Google app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Sender info
    $mail->setFrom('sysint8@gmail.com', 'IT490'); // sender address and name

    // get all leagues
    $leagueResult = getLeagues();

    if ($leagueResult['success']) {
        // loop through each
        foreach ($leagueResult['data'] as $league) {
            $leagueId = $league['id'];
            $leagueName = $league['name'];

            // get all users for league
            $userResult = getUsersByLeague($leagueId);

            if ($userResult['success']) {
		// get emails for each user
		$userIds = $userResult['data'];
                $emailResult = getUserEmails($userIds);

                if ($emailResult['success']) {
                    // send email to each
                    $emails = $emailResult['data'];

                    // send email and send
                    foreach ($emails as $email) {
                        $mail->clearAddresses(); // ensure fresh for next send
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = "Weekly Update for $leagueName";
                        $mail->Body = "Here is your daily update for league $leagueName. Make sure to check the leaderboard to see your current position.";
                        //$mail->AltBody = "Hello! Here is your weekly update for the $leagueName."; // can add if wanted

                        // Send
                        $mail->send();
                        echo "Email sent to $email for $leagueName\n";
                    }
                } else {
                    echo "Error fetching emails for league $leagueName: " . $emailResult['message'] . "\n";
                }
/*
                // SMS Functionality (1 phone number for testing)
                $phoneNumber = '8148535525'; // will loop through phone numbers
                $smsRecipient = "$phoneNumber@txt.att.net"; // at&t gateway

                $mail->clearAddresses(); // reset numbers
                $mail->addAddress($smsRecipient);

                // SMS content
                $mail->isHTML(false); // plain text
                $mail->Subject = ''; // not needed
                $mail->Body = "Weekly Update: Here's your update for the $leagueName.";

                // send
		if ($mail->send()) {
     			echo "SMS sent to $phoneNumber\n";
		} else {
			echo "SMS failed to send. Error: {$mail->ErrorInfo}\n";
	    	}
*/
	    } else {
                echo "Error fetching users for league $leagueName: " . $userResult['message'] . "\n";
            }
        }
    } else {
        echo "Error fetching leagues: " . $leagueResult['message'] . "\n";
    }
} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}\n";
}
?>
