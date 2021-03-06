<?php

require_once 'Models/UserDataSet.php';
session_start();

$view = new stdClass();
$view->pageTitle = 'Register';
$userDataSet = new UserDataSet();

$view->employees = $userDataSet->fetchEmployees();

/**
 *
 * * Send the values from the form to the createUser function in userDataSet and create a new User
 */
if(isset($_POST['signup'])){
    $firstname      = $_POST['firstname'];
    $lastname       = $_POST['lastname'];
    $email          = $_POST['email'];
    $password       = $_POST['password'];
    $userDataSet->createUser($firstname, $lastname, $email, $password);

}

if(isset($_SESSION['login'])){
    $view->email = $_SESSION['login']->getEmail();
}

require_once('Views/register.phtml');