<?php
include 'header.php';
include 'validation.php';
require_once 'client_rmq_db.php';

// fetch user's profile
$request = array();
$request['type'] = 'get_user_profile';
$request['user_id'] = $userId;

$response = createRabbitMQClientDatabase($request);

if ($response['success']) {
    $email = htmlspecialchars($response['user']['email']); // get user email
    $phoneNum = htmlspecialchars($response['user']['phoneNum'] ?? ''); // get user phone num if exists
} else {
    $error_message = htmlspecialchars($response['message']);
}

// form for updating empty phone number
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phoneNum'])) {
    $newPhoneNum = $_POST['phoneNum'];

    $request = array();
    $request['type'] = 'update_phone_number';
    $request['user_id'] = $userId;
    $request['phoneNum'] = $newPhoneNum;

    $updateResponse = createRabbitMQClientDatabase($request);

    if ($updateResponse['success']) {
        $success_message = "Phone number updated successfully.";
        $phoneNum = htmlspecialchars($newPhoneNum);
    } else {
        $error_message = htmlspecialchars($updateResponse['message']);
    }
} elseif (isset($_POST['remove_phone']) && $_POST['remove_phone'] === 'true') {
        // form to remove the phone number (set to NULL)
        $request = array();
        $request['type'] = 'update_phone_number';
        $request['user_id'] = $userId;
        $request['phoneNum'] = null;  // reset phone number in table

        $response = createRabbitMQClientDatabase($request);
        
        if ($response['success']) {
            echo "<script>alert('Phone number removed successfully.'); window.location.href = 'profile.php';</script>";
        } else {
            echo "<div class='alert alert-danger'>Failed to remove phone number: {$response['message']}</div>";
	}
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
	<h1>Contact Info</h1><br>

        <!-- Display user's email -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php else: ?>
            <div class="mb-3">
                <strong>Email: </strong> <?php echo $email; ?>
            </div>
	<!-- Display form so user can add phone number-->
	    <div class="mb-3">
                <strong>Phone Number: </strong> 
                <?php if (empty($phoneNum)): ?> +1
                    <form action="profile.php" method="POST" class="d-inline">
                        <input type="text" name="phoneNum" class="form-control d-inline w-50" placeholder="Enter phone number" maxlength="10" required>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                <?php else: ?>
		    +1 <?php echo $phoneNum; ?>
		        <form action="profile.php" method="POST" class="d-inline">
		            <input type="hidden" name="remove_phone" value="true">
		            <button type="submit" class="btn btn-danger btn-sm ml-2">Remove</button>
        		</form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Display phone number update errors if any -->
        <?php if (isset($update_error)): ?>
            <div class="alert alert-danger"><?php echo $update_error; ?></div>
        <?php endif; ?>


    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

