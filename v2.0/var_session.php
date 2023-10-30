 
<?php
//
//The library contains the database class and its from where the session starts
require 'library.php';
//
//Define stdClass
$x = new stdClass();
//
//get the session variables
//if the username is set return the username
if (isset($_SESSION['username'])){
    //
    $x->status=true;
    $x->username = $_SESSION['username'];
}
 else {
    $x->status = false;
}
//
//Ecode the object to pass it as a json 
echo json_encode($x);
//
//for deburging purposes 
//destroy the session varriables for deburging purposes 
session_destroy();

