<?php
require_once('client_rmq_deploy.php');
require_once('clusterFunctions.php');
$connection = createSSHConnection('172.23.193.68', 22);
$allBundles = parse_ini_file('../config/bundles.ini', true);

// echo var_dump($allBundles);
$systemRole = $allBundles["SystemData"]["ROLE"];
echo "Designated as role: " . $systemRole . PHP_EOL;

@$targetAction = $argv[1];
if ($targetAction == null) {
    echo "Available commands:\n\tpushBundle [BUNDLE_NAME]\n\tapproveBundle [BUNDLE_NAME] [VERSION] [PASS/FAIL]\n\tfanout [BUNDLE_NAME] [PROD|QA]\n\n";
    die();
}

if ($targetAction == "pushBundle") {
    echo $targetAction;
    // $outie = shell_exec('../../installScripts/upload.sh');
    // echo $outie;

    $bundleName = $argv[2];
    if ($bundleName == null) {
        echo "hey i need to know what bundle ur pushing". PHP_EOL;
        echo "make sure u use the ones ur role is compatible w. todo: var_dump the ini here". PHP_EOL;
        echo "try FrontendPhp / BackendPhp / BackendLogging". PHP_EOL;
        die();
    }
    echo var_dump($allBundles[$bundleName]);
    $filePath = $allBundles[$bundleName]["BUNDLE_PATH"];
    $bundleMachine = $allBundles[$bundleName]["BUNDLE_MACHINE"];
    $systemRole;
    @$override = $argv[3] == null ? false : ($argv[3] == "--override" ? true : false);
    echo var_dump($override);
    if ($systemRole != $bundleMachine && ($override == null || $override != true)) {
        echo "hey man ur doin smth u shouldnt be." . PHP_EOL;
        echo "add the --override option to this script as the third arg to get past this" . PHP_EOL;
        die();
    }

    echo "alright here's what we doin.\n";
    echo var_dump($filePath);
    // exec("scp -r $filePath deployer@172.23.193.68:/opt/store/$bundleName-v$versionNumber");
    // ssh2_exec($connection, "scp -r $filePath deployer@172.23.193.68:/opt/store/$bundleName-v$versionNumber");

    // publish intent to upload new bundle. no backing down now!
    $request = ["type" => "dryAddBundle", "bundleName" => $bundleName, "bundlePath" => $filePath, "bundleMachine" => $bundleMachine];
    $returnedResponse = sendToDeployment($request);
    // echo var_dump($returnedResponse);

    if (isset($returnedResponse["version"])) {
        echo "Pushing new bundle...\n";
        $versionNumber = $returnedResponse["version"];
        $realBundlePath = "/opt/store/$bundleName-v$versionNumber";
        // pack it up bc we can't xfer directories
        $thatPath = getRelativePath('/home/luke/git/IT490-Project/zDeploy/php', $filePath);
        echo var_dump($thatPath);
        exec("tar -czvf ../upload/sendoff.tar.gz  -C $thatPath .");
        // holding cell
        sendFile($connection, '../upload/sendoff.tar.gz', "/opt/store/$bundleName-v$versionNumber.tar.gz");

        // $res = ssh2_exec($connection, "/usr/bin/tar -xzvf /opt/store/$bundleName-v$versionNumber.tar.gz -C /home/luke/git/IT490-Project/deployment/bundles/$bundleName-v$versionNumber");
        $res = ssh2_exec($connection, "cd /opt/store; chmod ugo+x -R .; chmod ugo+x /opt/store/$bundleName-v$versionNumber.tar.gz; mkdir $bundleName-v$versionNumber; tar -xzvf /opt/store/$bundleName-v$versionNumber.tar.gz -C ./$bundleName-v$versionNumber; echo done; pwd; rm $bundleName-v$versionNumber.tar.gz");
        // $res = ssh2_exec($connection, "cat /opt/store/$bundleName-v$versionNumber.tar.gz | tar zxvf -");
        // echo var_dump(stream_get_contents($res));

        // /*
        $errs = ssh2_fetch_stream($res, 0);
        stream_set_blocking($errs, true);
        $result_err = stream_get_contents($errs);
        echo 'stderr: ' . $result_err;
        // */
    } else {
        echo "Critical error\n";
        die();
    }
} else if ($targetAction == "approveBundle") {
    @$bundleName = $argv[2];
    @$version = $argv[3];
    @$status = $argv[4];
    if (
        $bundleName == null ||
        $version == null ||
        $status == null
    ) {
        echo "Usage:\n\tapproveBundle [BUNDLE_NAME] [VERSION] [PASS/FAIL]\n";
        die();
    }

    $request = ["type" => "approveBuild", "bundleName" => $bundleName, "version" => $version, "status" => $status];
    $returnedResponse = sendToDeployment($request);
    echo var_dump($returnedResponse);

    //
} else if ($targetAction == "fanout") {
    @$bundleName = $argv[2];
    @$cluster = $argv[3]; // prod or qa
    if (
        $bundleName == null ||
        $cluster == null
    ) {
        echo "Usage:\n\rfanout [BUNDLE_NAME] [PROD/QA]\n";
        die();
    }

    $request = ["type" => "newFanout", "bundleName" => $bundleName, "cluster" => $cluster];
    $returnedResponse = sendToDeployment($request);
} else if ($targetAction == "switch") {
    @$bundleName = $argv[2];
    @$cluster = $argv[3]; // prod or qa
    @$version = $argv[4];
    if (
        $bundleName == null ||
        $cluster == null ||
        $version == null
    ) {
        echo "Usage:\n\rswitch [BUNDLE_NAME] [PROD/QA] [VERSION]\n";
        die();
    }

    $request = ["type" => "requestVersion", "bundleName" => $bundleName, "cluster" => $cluster, "version" => $version];
    $returnedResponse = sendToDeployment($request);
} else if ($targetAction == "rollback") {
    @$bundleName = $argv[2];
    @$cluster = $argv[3]; // prod or qa
    if (
        $bundleName == null ||
        $cluster == null
    ) {
        echo "Usage:\n\rrollback [BUNDLE_NAME] [PROD/QA]\n";
        die();
    }

    $request = ["type" => "rollback", "bundleName" => $bundleName, "cluster" => $cluster];
    $returnedResponse = sendToDeployment($request);
} else {
    echo "Available commands:\n\tpushBundle [BUNDLE_NAME]\n\tapproveBundle [BUNDLE_NAME] [VERSION] [PASS/FAIL]\n\tfanout [BUNDLE_NAME] [PROD|QA]\n\n";
}

exit();
?>
