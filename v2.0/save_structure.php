<?php
    //Enable reporting for all types
    set_error_handler(function($errno, $message, $filename, $lineno){
       throw new ErrorException($message, 0, $errno, $filename, $lineno); 
    });
    //
    //Access the definition of classes used in this project
    include "library.php";
     if(!isset($_REQUEST['dbname'])){
     throw new Exception("Error the log in credentials PASSWORD must be set");
  }
    //
    //create a coodinate for testing
    $coordinate= new stdClass();
    $coordinate->name= 'eureka_mobile';
    $coordinate->cx= 500;
    $coordinate->cy= 500;
     //
     //declare an array to store the coodinate so as to resemble the javascript
     $coordinates= [];
     array_push($coordinates, $coordinate);
    //
    //set the database name
    $dbname= $_REQUEST['dbname'];
   // $coordinates=$_REQUEST['coordinates'];
    //
    //create an instance of the database class 
    $dbase = new database($dbname);
    //
    //update the entity coodinates
    $dbase->update_entity_coordinates(json_encode($coordinates));
    
  