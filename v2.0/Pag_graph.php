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
     <title> Graph</title>
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
        ul {
          list-style-type: none;
          margin: 0;
          padding: 0;
          overflow: hidden;
          background-color: #333;
        }

        li {
          float: left;
        }

        li  {
          display: block;
          color: white;
          text-align: center;
          padding: 14px 16px;
          text-decoration: none;
        }

        li:hover {
          background-color: skyblue;
        }
        </style>
    </head>
    <body>
        <div id="navigation">
            <ul id="navigation_list">
              <li>Home</li>
              <li onclick="$page_graph.zoom(false)"><b>+</b></li>
              <li onclick="$page_graph.side_pan(true)"><a><b>&lt;</b></a></li>
              <li onclick="$page_graph.top_pan(true)"><b>˄</b></li>
              <li onclick="$page_graph.side_pan(false)"><b>&gt;</b></li>
              <li onclick='$page_graph.zoom(true)'><b>-</b></li>
              <li onclick='$page_graph.top_pan(false)'><b>˅</b></li>
              <li id="create_record" onclick="$page_graph.create_records()" >Create Record</li>
              <li id="review_record" onclick="$page_graph.review_records()">Review Record</li>
              <li id="save_structure" onclick="$page_graph.save_structure()">Save view</li>
              <li id="hide_entity" onclick="$page_graph.hide_element()">Hide Entity</li>
              <li id="show_entity" onclick="$page_graph.show_element()">Show Entity</li> 
              <li id="logout" onclick="$page_graph.loggingout()">Log Out</li>
              <li id="open_dbase" onclick="$page_graph.open_dbase()">Open database</li>
              <li id="select">Database names</li>
              <li id="close_dbase" onclick="$page_graph.close_dbase()">Close Database</li>
              <li id="alter_entity" onclick="$page_graph.entity_alter()">Edit Entity</li>
            </ul>
            <button id="login" hidden="true">Login to access services</button>
          </
        <div id="content">
            
            <svg height="100%" width="100%" viewbox="100 -100 3000 2400" onload="new dragndrop_group()"id="svg">
                
            </svg>
        </div>

    </body>
</html>
