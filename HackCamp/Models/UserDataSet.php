<?php

require_once('Models/Database.php');
require_once('Models/UserData.php');
require_once ('Models/project.php');

/*
 * query the database to get user information and projects 
 */
class UserDataSet
{



    protected $_dbHandle, $_dbInstance;

    public function __construct() {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getdbConnection();
    }

    /*
     * @param $email
     * @return bool
     *
     * This is a function to check whether an email already exists in the database
     * This is to prevent a user signing up again using the same email address already being used
     */
    private function emailInDatabase($email)
    {
        $exists = false;
        $sql = 'SELECT COUNT(employeeID) AS Total FROM employee WHERE email='."\"$email\"";
        //var_dump($sql);
        $statement = $this->_dbHandle->prepare($sql); // prepare a PDO statement
        $statement->execute(); // execute the PDO statement
        if($row = $statement->fetch())
        {
            if(!$row['Total']==0)
            {
                $exists = true;
            }

        }
        return $exists;
    }

    /**
     * @param $email
     * @return UserData
     */
    public function getUser($email)
    {
        $sqlQuery = 'SELECT employee.* FROM employee WHERE email='."\"$email\"";
        $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
        $statement->execute(); // execute the PDO statement
        $row = $statement->fetch();
        return $user = new UserData($row);
    }


    /**
     * @param $email
     * @param $password
     * @return bool
     *
     * This is a function to authenticate a returning user
     * Their email and password is checked against the data present in the database
     */
    public function checkLogIn($email, $password)
    {

        $password = md5($password); //Password Encryption
        $isLoggedIn = false; //Boolean used to maintain state

        $sqlCheck = 'SELECT COUNT(employeeID) AS Found FROM employee WHERE email='."\"$email\" AND password = \"$password\" ";
        

        $statement = $this->_dbHandle->prepare($sqlCheck); // prepare a PDO statement
        $statement->execute(); // execute the PDO statement
        if($row = $statement->fetch())
        {
            if(!$row['Found']==0)
            {
                $isLoggedIn = true; //If such user exists then we want to maintain state
                $_SESSION['logged_in'] = $this->getUser($email);
                $user = $this->getUser($email);
                $_SESSION['login'] = $user;
                return true;
            }
            else{
                return false;
            }
        }
        return $isLoggedIn;

    }
    
    /**
     * @param $firstName
     * @param $lastName
     * @param $email
     * @param $password
     *
     * These are the parameters used to create a new user when they are signing up.
     * Password is also encrypted using the MD5 hash method.
     */
    public function createUser($firstName, $lastName, $email, $password) {

        $email = htmlentities($email);
        if(!$this->emailInDatabase($email))
        {
            $firstName = htmlentities($firstName);
            $lastName = htmlentities($lastName);
            $password = htmlentities($password);
            $password = md5($password);
            $sqlQuery = 'INSERT INTO employee (firstname, lastname, password, email) VALUES('."\"$firstName\"".', '."\"$lastName\"".', '."\"$password\"".', '."\"$email\"".')';
            //var_dump($sqlQuery);
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement

            if($statement->execute()){
                echo 'User successfully registered';
            }
        }
        else
        {
            echo "Email already taken, please try another email!";
        }
    }


    //check whether a project already exists
    public function checkIfProjectExists($projectName){
        try {
            $sqlQuery = 'SELECT projectID FROM project WHERE name = ?; ';
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
            $statement->execute([$projectName]); // execute the PDO statement
            $row = $statement->fetch();
        }
        catch (PDOException $e){
            $e->getMessage();
        }

        return $row['projectID'];
    }

    //gets a list of employees
    public function fetchEmployees(){
        try {
            $sqlQuery = 'SELECT employeeID, firstname, lastname, email FROM employee ORDER BY employeeID;';
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
            $statement->execute(); // execute the PDO statement
        }
        catch (PDOException $e){
            $e->getMessage();
        }
        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = $row;
        }
        return $dataSet;
    }

    //gets the average hours worked by an employee for each project
    public function getAverageForProject($employeeID){
        try {
            $sqlQuery = 'SELECT hoursworked.projectID, project.name, SUM(TIMESTAMPDIFF(HOUR,start_time, end_time)) As TotalHours
                        FROM hoursworked INNER  JOIN project ON project.projectID = hoursworked.projectID 
                        WHERE employeeID = ?
                        GROUP BY hoursworked.projectID;';
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
            $statement->execute([$employeeID]); // execute the PDO statement
        }
        catch (PDOException $e){
            $e->getMessage();
        }
        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = $row;
        }
        return $dataSet;
    }

   /* query database to get a report on an employee 
    *retuning a report on how long the employee 
    * has worked on each project
    * columns returned:
    * 1) project name
    * 2) date 
    * 3) start time
    * 4) end time
    * 5) total hours worked
    */
    public function getTimeReport($employeeID){
        try {
            $sqlQuery = 'SELECT  project.name, 
                            date_format((hoursworked.start_time), \'%D %M %Y\') AS \'Date\',
                            date_format((hoursworked.start_time), \'%H:%i\') AS \'Start Time\',
                            date_format((hoursworked.end_time), \'%H:%i\') AS \'End Time\',
                            TIMESTAMPDIFF(HOUR,start_time, end_time) As TotalHours
                        FROM hoursworked INNER  JOIN project ON project.projectID = hoursworked.projectID 
                        WHERE employeeID = ?
                        ORDER BY Date ASC;';
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
            $statement->execute([$employeeID]); // execute the PDO statement
        }
        catch (PDOException $e){
            $e->getMessage();
        }
        $dataSet = [];
        //Fetch:assoc to prevent duplicates
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = $row;
        }
        return $dataSet;
    }

    /*
     * @param employeeID
     * return employee details
     */
    public function getEmployeeDetails($employeeID){
        try {
            $sqlQuery = 'SELECT employeeID, firstname, lastname 
                        FROM employee
                        WHERE employeeID = ?';
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
            $statement->execute([$employeeID]); // execute the PDO statement
        }
        catch (PDOException $e){
            $e->getMessage();
        }
        $dataSet = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = $row;
        }
        return $dataSet;
    }

    // given project name add data into database
    public function addProject($name){
        $query = "INSERT INTO project( name) VALUES(?);";
        $statement = $this->_dbHandle->prepare($query); // prepare a PDO statement
        $statement->execute([$name]);
    }

    /*
     * check for projects that have not been assigned to a user
     * @param email
     */
    public function fetchAvailableProjects($employeeID){
        try {
            $sqlQuery = 'SELECT projectID, name FROM project WHERE projectID NOT IN (SELECT projectID FROM assigned_project where employeeID = ? ) ORDER BY projectID';
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
            $statement->execute([$employeeID]); // execute the PDO statement
        }
        catch (PDOException $e){
            $e->getMessage();
        }
        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = $row;
        }
        return $dataSet;
    }
    
    /*
     * get a list of projects that have been assigned
     * @param employeeID
     */
    public function fetchAssignedProjects($employeeID){
        try {
            $sqlQuery = 'SELECT projectID, name FROM project WHERE projectID IN (SELECT projectID FROM assigned_project where employeeID = ? ) ORDER BY projectID';
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
            $statement->execute([$employeeID]); // execute the PDO statement
        }
        catch (PDOException $e){
            $e->getMessage();
        }
        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = $row;
        }
        return $dataSet;
    }

    
    /*
     * gets all projects available
     */
    public function fetchAllProjects(){
        try {
            $sqlQuery = 'SELECT projectID, name FROM project ORDER BY projectID';
            $statement = $this->_dbHandle->prepare($sqlQuery); // prepare a PDO statement
            $statement->execute(); // execute the PDO statement
        }
        catch (PDOException $e){
            $e->getMessage();
        }
        $dataSet = [];
        while ($row = $statement->fetch()) {
            $dataSet[] = $row;
        }
        return $dataSet;
    }

    /*
     * assign a project to a user
     * @param projectID
     * @param employeeID
     */
    public function assignProject($projectID, $employeeID){
        $query = "INSERT INTO assigned_project(projectID, employeeID) VALUES(?,?);";
        $statement = $this->_dbHandle->prepare($query); // prepare a PDO statement
        $statement->execute([$projectID, $employeeID]);
    }

}
