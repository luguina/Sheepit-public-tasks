<?php

require_once(__DIR__.'/vendor/autoload.php');

// Local file to be uploaded
$local_file = 'test.png';

// Initialise the GDrive control class with the demo user (that will be replaced with the SheepIt active user)
$cloud = new CloudFileStorageGoogleDrive("userID");

if ($cloud->isGDriveConnected()) {
    // If we already have the token and it's valid, initialise the SheepIt folder (check that exists and otherwise,
    // create it) and send the file
    $cloud->initialiseSheepItFolder();
    $upload_result = $cloud->add($local_file);

    // If we want to add any description to the files (ie the name of the SheepIt project, or the number of frame or
    // any other description that will be searchable in GDrive, use this method instead.
    //$upload_result = $cloud->add($local_file, 'this is the description for this file');

    if ($upload_result == GDRIVE_UPLOAD_OK) {
        print('The file '.$local_file.' was successfully uploaded.');
    }
    else if ($upload_result == GDRIVE_LOCAL_FILE_NOT_FOUND) {
        print('The file '.$local_file.' doesn\'t exist in the specified path.');
    }
    else if ($upload_result == GDRIVE_LOCAL_ERROR) {
        print('Unknown error when trying to add a new file');
    }
    else if ($upload_result == GDRIVE_INVALID_USER_TOKEN) {
        print('Trying to add a new file and the user hasn\'t connected Google Drive properly');
    }
}
else {
    // If the user hasn't connected the GDrive with SheepIt (or any other issue with the user token -expired, invalid,
    // credentials revoked by the user, etc- then restart the Authentication process.
    $cloud->startGDriveOAuthProcess();
}

?>