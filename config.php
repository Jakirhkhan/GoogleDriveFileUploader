
<?php
define('CMD_ROOT', '__DIR__');
define('BACKUP_DIR','E:\xampp\htdocs\c4s\temp\backup');
define('FOLDER_ID','0B3xj84v0iahvUkxtajlSVTBSaWc');
define('FILE_REMOVE_DAYS_BEFORE',5);

define('APPLICATION_NAME', 'Drive API PHP Quickstart');
define('CREDENTIALS_PATH', CMD_ROOT.'/.credentials/drive-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/drive-php-quickstart.json
define('SCOPES', implode(' ', array(
        Google_Service_Drive::DRIVE_FILE,
        Google_Service_Drive::DRIVE
    )
));
