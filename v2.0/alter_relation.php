<?php
   $name = $_GET['id'];
   $entites = explode (".", $name);  
?>
<html>
    <head>
        <script src="library.js"></script>
        <script src="page_graph.js"></script>
        <style>
            button{
                background-color: lightseagreen;
                color: red;
                padding: 15px 32px;
                font-size: 16px;
              }
              
            #relation{
                  display: flex;
            }
            
            input{
              background-color: bisque;
              color:green;
            }
        </style>
        
    </head>
    
    <body>
        <div id="relation">
        <select id='start' onchange="page_graph.fill_end()">
            <option><?php echo "$entites[0]";?></option>
            <option><?php echo "$entites[1]";?></option>
        </select>
        
        <div class='type'>        
            <input class='is_a' type="radio" name="type" id="is_a" value="is_a"> is_a<br>

            <div class='has_a' id = 'hasa'>        
                <input type="radio" name="type" value="has_a" id="has_a">
                <input type="text" placeholder="name of the relation" id="title">
            </div>
        </div>       
        <span id='end'>end relation</span>
        </div>
        <button id='save'>Save</button>
        <button id='cancel' onclick="window.close()">cancel</button>
        
    </body>
</html>
