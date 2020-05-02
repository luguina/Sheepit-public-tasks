<?php

define('GDRIVE_UPLOAD_OK', 0);
define('GDRIVE_LOCAL_FILE_NOT_FOUND', 1);
define('GDRIVE_LOCAL_ERROR', 2);
define('GDRIVE_INVALID_USER_TOKEN', 3);

class CloudFileStorageGoogleDrive
{
    public static $type = 'GoogleDrive';

    private $googleClient;
    private $sheepItDefaultDirectoryID;
    private $sheepItDefaultDirectoryName = 'SheepIt';
    private $userTokenValid = false;

    public function __construct($userID) {
        $this->googleClient = $this->getGoogleClient($userID);
    }

    public function initialiseSheepItFolder() {
        // first check if the directory exists. It's important to do this check bc the user might be new or has
        // deleted previously the directory. If sheepItDefaultDirectoryName is empty then files are placed in the
        // root directory of the user's GDrive (not recommended)
        //
        // If it doesn't exist, create it now
        if ($this->userTokenValid) {
            if ($this->sheepItDefaultDirectoryName != '' and !$this->sheepItDirectoryExists($this->googleClient)) {
                $this->createSheepItRootDirectory();
            }
        } else {
            print('ERROR: Cannot check or create ' . $this->sheepItDefaultDirectoryName . ' directory. User token is invalid.');
        }
    }

    /**
     * Returns the result of uploading the file
     * @return int with the result of uploading the file
     */
    public function add($local_path) {
        return $this->addWithDescription($local_path, '');
    }

    /**
     * Returns the result of uploading the file
     * @return int with the result of uploading the file
     */
    public function addWithDescription($local_path, $file_description) {
        if (!$this->userTokenValid) {
            print('ERROR: Cannot add the file ' . $local_path . '. User token is invalid.');
            return GDRIVE_INVALID_USER_TOKEN;
        }

        if (file_exists($local_path)) {
            try {
                $service = new Google_Service_Drive($this->googleClient);

                // Initialise the GDrive name (same as local) and associate the proper file MimeType
                $file = new Google_Service_Drive_DriveFile();
                $file->setName(basename($local_path));
                $file->setMimeType(mime_content_type($local_path));

                // If the file include any additional description, attach to the uploaded file
                if ($file_description != '') {
                    $file->setDescription($file_description);
                }

                // If the default SheepIt directory is defined, specify the upload directory
                if ($this->sheepItDefaultDirectoryID != '') {
                    $file->setParents(array($this->sheepItDefaultDirectoryID));
                }

                // Upload the file to GDrive in resumable mode
                $createdFile = $service->files->create(
                    $file,
                    array(
                        'data' => file_get_contents($local_path),
                        'uploadType' => 'resumable'
                    )
                );
            } catch (Exception $e) {
                return GDRIVE_LOCAL_ERROR;
            }

            return GDRIVE_UPLOAD_OK;
        } else {
            return GDRIVE_LOCAL_FILE_NOT_FOUND;
        }
    }


    private function sheepItDirectoryExists($client) {
        $service = new Google_Service_Drive($client);

        // Check if the default directory exists and it's active (hasn't been deleted)
        $optParams = array(
            'pageSize' => 1,
            'fields' => 'nextPageToken, files(id, name)',
            'q' => 'name = \'' . $this->sheepItDefaultDirectoryName . '\' and mimeType = \'application/vnd.google-apps.folder\' and trashed = false'
        );

        try {
            $results = $service->files->listFiles($optParams);

            if (count($results->getFiles()) == 0) {
                // Zero results returned meaning that directory doesn't exist
                return false;
            } else {
                // Directory exists, so store the GDrive ID for this folder
                $this->sheepItDefaultDirectoryID = $results['files'][0]['id'];

                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    private function createSheepItRootDirectory() {
        $service = new Google_Service_Drive($this->googleClient);

        $dir = new Google_Service_Drive_DriveFile();
        $dir->setName($this->sheepItDefaultDirectoryName);
        $dir->setMimeType('application/vnd.google-apps.folder');

        $folder = $service->files->create($dir);

        if ($folder['id'] != '') {
            // The folder has been created, so take the unique ID and store it for futher operations
            $this->sheepItDefaultDirectoryID = $folder['id'];
            return true;
        } else {
            return false;
        }
    }


    private function getGoogleClient($userID) {
        // Configure the client
        $client = new Google_Client();

        // The credentials.json file must be downloaded from the Google APISs console. Corresponds to an OAuth2.0
        // client ID. The section to add a new ID is in APIs & Services > Credentials > Create credentials. Once
        // created, download the credentials and copy the file in a server folder accessible by this PHP script.
        $client->setAuthConfig('credentials.json');

        $client->setApplicationName('SheepIt Render Farm');
        $client->setScopes(Google_Service_Drive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->setPrompt('select_account consent');

        // This is the URL that Google will invoke at the end of the authentication process with the proper user's long
        // lasting token that we will use to access the user's GDrive. This URL must match with the one inserted in
        // the APIs & Services > Credentials > OAuth 2.0 Client IDs > Authorized redirect URIs option in the Google
        // Cloud console
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/storeUserToken.php';
        $client->setRedirectUri($redirect_uri);

        // Now we will load the authorized token from the requested user. In this case, and for a demo purposes I
        // am using a file called <userID>.json, where <userID> is the unique SheepIt username, but probably a good
        // idea is to store and retrieve the token from a propoer field in the user profile table.
        $tokenPath = $userID . '.json';

        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) { // If there is no previous token or it's expired.
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                // Search for any error
                if (array_key_exists('error', $newAccessToken)) {
                    $this->userTokenValid = false;
                } else {
                    $client->setAccessToken($accessToken);
                    $this->userTokenValid = true;
                }
            } else {
                $this->userTokenValid = false;
            }
        } else {
            try {
                // There is no way to know if the credentials are not valid until we try an operation in GDrive
                // so here we try to see if the SheepIt directory exists
                $this->sheepItDirectoryExists($client);
                $this->userTokenValid = true;
            } catch (Exception $e) {
                $this->userTokenValid = false;
            }
        }

        return $client;
    }


    public function startGDriveOAuthProcess() {
        // Jump to Google's authentication process to request authorization from the user.
        $authUrl = $this->googleClient->createAuthUrl();
        header('location: ' . $authUrl);
    }


    public function getGDriveOAuthProcessURL() {
        // Return the URL that will start the Google's authentication process
        return $this->googleClient->createAuthUrl();
    }


    public function isGDriveConnected() {
        return $this->userTokenValid;
    }
}

?>