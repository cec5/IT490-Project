<?php
// Define the base directory for the zip files
$baseDir = __DIR__ . "/../store";

// 127.0.0.1/request.php?branch=dev&version=1.0&role=frontend

// Get parameters from query string
$branch = filter_input(INPUT_GET, 'branch', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$version = filter_input(INPUT_GET, 'version', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$role = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Valid paths and roles
$validBranches = ['qa', 'dev', 'prod'];
$validRoles = ['backend', 'dmz', 'frontend'];

// Invalid input
if (!in_array($branch, $validBranches) || !in_array($role, $validRoles)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid parameters provided.";
    exit;
}

// Check the version
if ($version === 'latest') {
    // Use 'latest' as the valid version directory
    $filePath = "$baseDir/latest/$branch/$role/package.zip";
} else {
    // Check for other versions, assuming they may or may not exist
    $filePath = "$baseDir/$version/$branch/$role/package.zip";
    if (!file_exists($filePath)) {
        // originally was going to default to latest, better to 400 fail

        // $filePath = "$baseDir/latest/$branch/$role/package.zip";
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid version provided or file does not exist.";
        exit;
    }
}

// if we asked for the latest but there is none...
// basically we're cooked
if ($version === 'latest' && !file_exists($filePath)) {
    header("HTTP/1.1 404 Not Found");
    echo "Requested file not found.";
    exit;
}

// Serve the file for download
$returnHeader  = "Content-Type: application/zip" . "\n";
$returnHeader .= "Content-Disposition: attachment; filename=" . basename($filePath) . "\n";
$returnHeader .= "Content-Length: " . filesize($filePath);
header($returnHeader);
readfile($filePath);

?>
