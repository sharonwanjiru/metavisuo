<?php
    //We require the sql file since it is where more methords are defined that are designed 
    //to support sql
    require 'sql_library.php';
    
    $dbname=  "mullco_rental";
    //Create the php database and the affected entity
    $dbase = new database($dbname, $username = 'mutallco',$password = 'mutall_2015');
    
    
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="home.css">
        <link rel="stylesheet" type="text/css" href="page_review.css">
        <script src='page_record.js'></script> 
        <script src='library.js'></script> 
        <script src='page_graph.js'></script> 
    </head>
    <body>
        <?php
           $dbase->export_structure();
        ?>
        
    </body>
</html>
