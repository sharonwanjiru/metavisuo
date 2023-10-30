<?php
    //
    //Enable reporting for all types
    set_error_handler(function($errno, $message, $filename, $lineno){
       throw new ErrorException($message, 0, $errno, $filename, $lineno); 
    });
    //
    //Access the definition of classes used in this project
    include "library.php";
    //
    //Get the database name from the url 
    $request =json_encode($_REQUEST);
    
?>

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="graph.css">
        <script src='dragndrop_library.js'></script> 
        <script src='library.js'></script>
         <script src="./node_modules/kld-intersections/dist/index-umd.js"></script>      
       
        <script src='page_graph.js'></script>      
        <script>
           //
           //Create a page graph object using the database from php
           var $page_graph = new page_graph(<?php echo $request; ?>);
           
        </script>

        <style>
            li {
                list-style-type: none;
                margin: 0;
                padding: 0;
                overflow: hidden;
                background-color: #333;
              }

            body {
              font-family: Arial, Helvetica, sans-serif;
              margin: 0;
            }
            .sub{
                display: block;
            }

            .navbar {
              overflow: hidden;
              background-color: #333; 
            }
            .navbar li {
              float: left;
              font-size: 16px;
              color: white;
              text-align: center;
              padding: 14px 16px;
              text-decoration: none;
            }
            .subnav {
              float: left;
              overflow: hidden;
            }

            .subnav .subnavbtn {
              font-size: 16px;  
              border: none;
              outline: none;
              color: white;
              padding: 14px 16px;
              background-color: inherit;
              font-family: inherit;
              margin: 0;
            }

            .navbar a:hover, .subnav:hover .subnavbtn {
              background-color: red;
            }
            .navbar li:hover, .subnav:hover .subnavbtn {
              background-color: red;
            }

            .subnav-content {
              display: none;
              position: absolute;
              left: 0;
              background-color: aquamarine;
              width: 100%;
              z-index: 1;
            }
            .subnav-content li {
              float: left;
              color: white;
              text-decoration: none;
              display: block;
            }
            .subnav-content li:hover {
              background-color: #eee;
              color: black;
            }
            .subnav:hover .subnav-content {
              display: block;
            }
        </style>
    </head>
    <body>
        <div class="navbar">
          <li>Home</li>
          <div class="subnav">
            <button class="subnavbtn">Window view</button>
            <div class="subnav-content">
              <li onclick="$page_graph.zoom(false)"><b>+</b></li>
              <li onclick="$page_graph.side_pan(true)"><a><b>&lt;</b></a></li>
              <li onclick="$page_graph.top_pan(true)"><b>˄</b></li>
              <li onclick="$page_graph.side_pan(false)"><b>&gt;</b></li>
              <li onclick='$page_graph.zoom(true)'><b>-</b></li>
              <li onclick='$page_graph.top_pan(false)'><b>˅</b></li>
            </div>
          </div> 
          <div class="subnav">
            <button class="subnavbtn">Entity Operations</button>
            <div class="subnav-content">
             <li id="create_record" onclick="$page_graph.create_records()" >Create Record</li>
             <li id="review_record" onclick="$page_graph.review_records()">Review Record</li>
             <li id="update_record">Update record</li> 
             <li id="delete_record">Delete record</li>  
            </div>
          </div> 
          <div class="subnav">
            <button class="subnavbtn">Entity design</button>
            <div class="subnav-content">
              <li id="hide_entity" onclick="$page_graph.hide_element()">Hide Entity</li>
              <li id="show_entity" onclick="$page_graph.show_element()">Show Entity</li>
              <li id="hidden_entities">Hidden entities</li>
              <li id="alter_entity" onclick="$page_graph.entity_alter()">Edit Entity</li>
              <li id="create_entity">Create Entity</li>
              <li id="delete_entity">Delete Entity</li>  
            </div>
          </div>
          <div class="subnav">
            <button class="subnavbtn">Column design</button>
            <div class="subnav-content">
                <li id="edit_column" onclick="$page_graph.alter_column()" class="sub">Edit column</li>
                <li id="create_column" class="sub">create column</li>
                <li id="delete_column" class="sub">Delete column</li> 
            </div>
          </div>
          <div class="subnav">
            <button class="subnavbtn">Database</button>
            <div class="subnav-content">
             <li id="open_dbase" onclick="$page_graph.open_dbase()">Open database</li>
              <li id="select">Database names</li> 
              <li id="close_dbase" onclick="$page_graph.close_dbase()">Close Database</li>
            </div>
          </div>
          <div class="subnav">
            <button class="subnavbtn">view</button>
            <div class="subnav-content">
             <li id="save_structure" onclick="$page_graph.save_structure()">Save view</li>              
            </div>
          </div>
         <li id="logout" onclick="$page_graph.loggingout()">Log Out</li>        
        <li><button id="login" hidden="true">Login to access services</button> </li>    
        </div>
        <div style="padding:0 16px">
        </div>
        <div id="content">
            <svg height="100%" width="100%" viewbox="100 -100 3000 2400" onload="new dragndrop_group()"id="svg">
            </svg>
        </div>
    </body>
</html>
