<?php
    //We require the sql file since it is where more methords are defined that are designed 
    //to support sql
    require 'sql_library.php';
    
    //
    //Retrieve the quey string information the entity name and the database name 
    if(isset($_GET['ename'])){$ename=  $_GET['ename'];}
    else { throw new Exception("set the entity name");}
    //
    //
     if(isset($_GET['dbname'])){$dbname=  $_GET['dbname'];}
     else { throw new Exception("set the database name");}
     //
     //
    if(isset($_GET['primary'])){$primary=  $_GET['primary'];}
    else{$primary=null;}
    //
    //
    if(isset($_GET['attributes'])){$attributes=  $_GET['attributes'];}
     else { throw new Exception("There are no attributes to modify");}
    //
    //Decode the attributes to create a php array
    $values = json_decode($attributes); 
    //
    $table= new sql\table($dbname,$ename);
    $record= new \sql\record($dbname,$ename, $values)
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="home.css">
        <link rel="stylesheet" type="text/css" href="page_review.css">
        <script src='page_review.js'></script> 
    </head>
    <body>
        <!--records -->
        <div id="content">
            <?php    
               $record->delete($primary);
            ?>
        </div>
        
    </body>
</html>
