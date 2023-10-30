<?php
    //We require the sql file since it is where more methords are defined that are designed 
    //to support sql
    require 'sql_library.php';
    
    //
    //Retrieve the quey string information the entity name and the database name 
    $ename=  $_GET['ename'];
    $dbname=  $_GET['dbname'];
    //
    $select= new \sql\selector($ename, $dbname);
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="home.css">
        <link rel="stylesheet" type="text/css" href="page_review.css">
        <script src='page_record.js'></script> 
         <script>
            function save(){
                const tr= document.querySelector('.clicked');
                //
                //If nothing was selected alert 
                if(tr===null){
                    alert ('select an option');
                    return;
                }
                //
                //Get the td
                const tds=tr.childNodes;
                //get the title of the primary
                const primary =eval(tds[0].getAttribute('title'));
                //
                //Get the referenced table 
                const ref = tds[0].getAttribute('ref');
                //
                //to get the friendly 
                const friendly= tds[1].textContent;
                //
                window.selected_={"primary":primary, "friendly":friendly,"ref_table_name":ref};
                //
                //close the window
                window.close();
            }
        </script>
    </head>
    <body onbeforeunload="record.get_data(window)">
        <!--navigator-->
        <div id="select">
            <button onclick=" save()" class="button"/>okay</button>
            <input class="button" value="cancel" onclick="window.close()"/>
            <input type="button" value="create <?php echo $ename; ?>" onclick="record.create_selector_record()" />
        </div>
        <!--records -->
        <div id="content">
            <?php
               $select->show($dbname);
            ?>
        </div>
        
    </body>
</html>
