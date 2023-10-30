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
    //Assuminmg that there may be a database request, pass this structure to
    //construct the page graph
    $request =json_encode( $_REQUEST );
    
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="home.css">
        <script src='dragndrop_library.js'></script> 
        <script src='library.js'></script>
        <script src="./node_modules/kld-intersections/dist/index-umd.js"></script>      
       
        <script src='page_graph.js'></script>      
       <script>
           //
           //Create a page graph object using the database from php
           var $page_graph =new page_graph(<?php  echo $request;?>);
        </script>
        <style>
        </style>
    </head>
    <body>
        <div class="navbar">
            <div class="dropdown">
              <button class="dropbtn">Entity operations
                <i class="fa fa-caret-down"></i>
              </button>
              <div class="dropdown-content">
                  <li id="alter_entity" onclick="page_graph.display_entity()">Display entity</li>
                  <li id="review_record" onclick="page_graph.review_entity()">Review Entity</li>
                   
              </div>
            </div> 
            <div class="dropdown">
              <button class="dropbtn">Selected  
                <i class="fa fa-caret-down"></i>
              </button>
              <div class="dropdown-content">
                  <li id="alter_entity" onclick="page_graph.edit_relation()">Edit relation</li>
                  <li id="alter_entity" onclick="page_graph.edit_attributes()">Edit attributes</li>
                  <li id="alter_entity" onclick="page_graph.display_entity()">Edit entity</li>
                  <li id="hide_entity" onclick="page_graph.hide_element()">Hide Selected</li>
                  <li id="delete_entity">Delete selected</li>
              </div>
            </div>
            <div class="dropdown">
              <button class="dropbtn">view
                <i class="fa fa-caret-down"></i>
              </button>
              <div class="dropdown-content">
                <li id="save_structure" onclick="page_graph.save_view()">Save view</li>
                <li id="show_entity" onclick="$page_graph.show_element()">Show Entity</li>
                <li id="hidden_entities">Hidden entities</li>
              </div>
            </div> 
        </div>
        <div id="select">
            <button type="button" onclick="page_graph.zoom(false)"><b>+</b></button>
            <button type="button" onclick="page_graph.side_pan(true)"><b>&lt;</b></button>
            <button type="button" onclick="page_graph.top_pan(true)" ><b>˄</b></button>
            <button type="button"onclick="page_graph.side_pan(false)"><b>&gt;</b></button>
            <button type="button" onclick='page_graph.zoom(true)'><b>-</b></button>
            <button type="button" onclick="page_graph.top_pan(false)"><b>˅</b></button>
            <select id="selector" onchange="page_graph.changedb()" placeholder="select your database"> </select>
            <select id="hidden" onchange="page_graph.show_entity()"placeholder="select to show the hidden entities">
                <option></option>
            </select>
            <button id="close_dbase" onclick="page_graph.close_dbase()">Close database</button> 
       </div> 
            <div id="content">
                <svg height="100%" width="100%" viewbox="100 -100 3000 2400" onload="new dragndrop()"id="svg">
                </svg>
            </div>
       
</body>
</html>
