<?php

require_once(__DIR__.'/vendor/autoload.php');

// Initialise the GDrive control class with the demo user
$cloud = new CloudFileStorageGoogleDrive("userID");

?>

<html>
    <body>
    <h1>Google Drive connector for SheepIt Render Farm</h1>
        <div>
<?php if (!$cloud->isGDriveConnected()): ?>
            <div>
                <a class='login' href='<?= $cloud->getGDriveOAuthProcessURL() ?>'>Connect with my Google Drive</a>
            </div>
<?php else: ?>
          <div>
            <p>Your GDrive is now connected with SheepIt:</p>
            <ul>
              <li><a href="main.php">Upload a test file</a></li>
            </ul>
          </div>
<?php endif ?>
        </div>
    </body>
</html>