<?php
/**
 * Created by Jakir Hosen Khan.
 * User: Jakir Hosen Khan
 * Date: 4/28/16
 * Time: 6:42 PM
 */

require __DIR__ . '/vendor/autoload.php';
require 'config.php';


class GoogleDriveApi {

    function setToken(){
        $this->getClientInfo();
        die;
    }

    /*
     * Set client authorized
     * Delete Drive previous (certain days) backup
     *
     */
    function FileUpload(){
        $client = $this->getClientInfo();
        $this->previousFileDelete($client);
        $backupFiles = $this->getSqlBackupFiles();
//        $this->pr($backupFiles);die;
        if(!empty($backupFiles)){
            foreach($backupFiles as $backup){
                $info = $this->fileUploadInoGoogleDrive($client,$backup);
                print_r($info);
                print "\n";
            }
        }
        die;
    }

    function FileList($service=null){
        // Print the names and IDs for up to 10 files.
        $optParams = array(
            'pageSize' => 10,
            'fields' => "nextPageToken, files(id, name)"
        );
        $results = $service->files->listFiles($optParams);

        if (count($results->getFiles()) == 0) {
            print "No files found.\n";
        } else {
            print "Files:\n";
            foreach ($results->getFiles() as $file) {
                echo '<br/>';
                printf("%s (%s)\n", $file->getName(), $file->getId());
            }
        }
    }


    //Delete file before 10days
    function previousFileDelete($client){
        $service = new Google_Service_Drive($client);
        $fileList = $this->getDeleteList($service);
        if(!empty($fileList)){
            foreach($fileList as $fileId=>$file){
                $this->deleteFile($service,$fileId);
            }
        }
        return;
    }

    // File upload into google drive
    function fileUploadInoGoogleDrive($client,$fileName=''){
        $service = new Google_Service_Drive($client);
        try
        {
            $file = new Google_Service_Drive_DriveFile();
            if(FOLDER_ID){
                $folderId = FOLDER_ID;
            }
            else{
                return "Folder not found!!";
            }
            $file->setParents(array($folderId));

            $fileInfoArr = $this->getFileList($service);
            if(in_array($fileName,$fileInfoArr)){
                return strtoupper($fileName).' File already uploaded';
            }else{
                $file->setName($fileName);
                $file->setDescription('A test document');
                $file->setMimeType('application/octet-stream');

                $backup_root_dir = $this->getBackupPath();
                $backupFile = $backup_root_dir . '\\'.$fileName;

                $data = file_get_contents($backupFile);
                $createdFile = $service->files->create($file, array(
                    'data' => $data,
                    'mimeType' => 'application/octet-stream',
                    'uploadType' => 'multipart'
                ));
                return strtoupper($fileName).' successfully uploaded';
            }

        }
        catch (Exception $e)
        {
            return $e->getMessage();
        }
    }

    /**
     * Permanently delete a file, skipping the trash.
     *
     * @param Google_Service_Drive $service Drive API service instance.
     * @param String $fileId ID of the file to delete.
     */
    function deleteFile($service, $fileId) {
        try {
            $service->files->delete($fileId);
        } catch (Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
    }
    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    function getClientInfo() {
        $client = new Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
//        $client->setAuthConfig(CLIENT_SECRET);
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');
        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory(CREDENTIALS_PATH);
        if (file_exists($credentialsPath)) {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);

            // Store the credentials to disk.
            if(!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);
        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, $client->getAccessToken());
        }
        return $client;
    }
    protected function getFileList($service){
        $results = $this->getFiles($service);
        $fileInfoList = array();
        if (count($results->getFiles()) == 0) {
        } else {
            foreach ($results->getFiles() as $file) {
                $fileInfoList[$file->getId()] = $file->getName();
            }
        }
        return $fileInfoList;
    }
    protected function getDeleteList($service){
        $results = $this->getFiles($service);
        $days_before10 = date('Y-m-d', strtotime('-'.FILE_REMOVE_DAYS_BEFORE.' days', strtotime(date('Y-m-d'))));
        $fileInfoList = array();
        if (count($results->getFiles()) == 0) {
        } else {
            foreach ($results->getFiles() as $file) {
                $cdate = date('Y-m-d',strtotime($file->getCreatedTime()));
//                echo '  '. $cdate.'  | ';
                if($cdate<$days_before10){
                    $fileInfoList[$file->getId()] = $file->getName();
                }
            }
        }
        return $fileInfoList;
    }
    protected function getFiles($service){
        $file = new Google_Service_Drive_DriveFile();
        if(FOLDER_ID){
            $folderId = FOLDER_ID;
        }
        else{
            return "Folder not found!!";
        }
        $file->setParents(array($folderId));
        $optParams = array(
            'q' => "'".$folderId."' in parents",
            'fields' => "nextPageToken, files(id, name,createdTime)"
        );
        return $service->files->listFiles($optParams);
    }
    protected function getBackupPath(){
        $dir = BACKUP_DIR;
        if(!is_dir($dir)){
            mkdir($dir,0777);
            file_put_contents($dir . '/index.php', 'You are not allowed to be here');
        }
        return realpath($dir);
    }
    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    protected function expandHomeDirectory($path) {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }
    //only allow *.sql && *.zip files
    protected function getSqlBackupFiles(){
        $backupFileDir = $this->getBackupPath();
        $backUpSqlFiles = array();
        $sourceFiles = scandir($backupFileDir);
        foreach ($sourceFiles as $srcFile){
//            if($srcFile != '.' && $srcFile !=='..' && $srcFile !=='.php'){
//                $backUpSqlFiles[] = $srcFile;
//            }
            if(preg_match('/.sql$/', $srcFile) || preg_match('/.zip$/', $srcFile)){
                $backUpSqlFiles[] = $srcFile;
            }
        }
        return $backUpSqlFiles;
    }

    function pr($data){
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }


} 