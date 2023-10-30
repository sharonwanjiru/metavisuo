<?php
    //We require the sql file since it is where more methords are defined that are designed 
    //to support sql
    require 'sql_library.php';
    
    //
    //Retrieve the quey string information the entity name and the database name 
    $ename=  $_GET['ename'];
    $dbname=  $_GET['dbname'];
    $dbase_= $_SESSION[$dbname];
     //
    //create the editor sql for presentation
     $edit= new sql\editor($ename,$dbname);
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
        <!--navigator-->
        <div id="select">
            <button class="button"  onclick="record.update()"/>edit_item</button>
            <button onclick="record.data(true)" class="button"/>save record </button>
            <button onclick="record.data(false)" class="button" />delete_record</button>
            <button onclick="record.create()" class="button"/>create_record</button>
        <button class="button"/>refresh</button> 
        <button onclick="window.close()" class="button"/>Back </button>
        </div>
        <!--records -->
        <div id="content">
            <?php
               $edit->show($dbname);
            ?>
        </div>
        
    </body>
</html>
