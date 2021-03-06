<?php

require_once('Models/Database.php');
require_once('Models/UserData.php');

class UploadFunctionality{
    protected $_dbHandle, $_dbInstance;

    public function __construct()
    {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getdbConnection();
    }

    public function uploadFile($employeeID, $fileName, $fileLocation){
        //upload file into database 
        $sqlQuery = 'INSERT INTO file_uploads(employeeID, file_name, file_location) VALUES('."\"$employeeID\"".', '."\"$fileName\"".', '."\"$fileLocation\"".')';
        $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement

        if($statement->execute()){
            $_SESSION['fileUploaded'] = "You have successfully uploaded the file";
            $this->updateSignedHours($employeeID);
        }
    }
    //once user uploads file call this method to indicate the user has signed in
    public function updateSignedHours($employeeID){
        $sqlQuery = 'UPDATE hoursworked JOIN file_uploads SET signed = 1 WHERE file_uploads.employeeID ='."\"$employeeID\"";
        $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
        $statement->execute();
    }
}
