<?php

require_once(__DIR__.'/vendor/autoload.php');

$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/demo.php';

$client = new Google_Client();
$client->setAuthConfig('credentials.json');
$client->setApplicationName('SheepIt Render Farm');
$client->setScopes(Google_Service_Drive::DRIVE_FILE);
$client->setAccessType('offline');

if (isset($_GET['code'])) {
    print($_GET['code']);

    // Capture user's Google drive token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Capture the unique user identifier from the session and store it. In this particular example the token is stored
    // in a file called <userID>.json, where <userID> is the unique SheepIt username, but probably a good idea is to
    // store the token in the SheepIt's database, as another field in the user profile table.
    //$token = $_SESSION['username'];
    $tokenPath = 'userID.json';

    // Save the token to a file.
    if (!file_exists(dirname($tokenPath))) {
        mkdir(dirname($tokenPath), 0700, true);
    }
    file_put_contents($tokenPath, json_encode($client->getAccessToken()));

    // redirect back to the initial page
    header('Location: ' . $redirect_uri);
}
?>
