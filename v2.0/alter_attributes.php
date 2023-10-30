<!DOCTYPE html>
<html>
    <head>
        <style>
            #column {
                font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
                border-collapse: collapse;
                width: 100%;
              }

              #column td, #column th {
                border: 1px solid #ddd;
                padding: 8px;
              }

              #column tr:nth-child(even){background-color: #f2f2f2;}

              #column tr:hover {background-color: #ddd;}

              #column th {
                padding-top: 12px;
                padding-bottom: 12px;
                text-align: left;
                background-color: #4CAF50;
                color: white;
              }

            button{
                background-color: lightseagreen;
                border: none;
                color: red;
                padding: 15px 32px;
                display: inline-block;
                font-size: 16px;
                border-radius: 50px;
              }
              #cancel{
                  float: right;
              }
              label{
                display:block;
                color: blue;
                margin-top: 2%;
              }
              input{
                background-color: bisque;
                color:green;
               }
        </style>
    </head>
    <body>
       <h2></h2>
       <table style="width:100%" id="column">
            <tr>
                <th colspan="2" id="entity_name">Entity name</th>
          </tr>
          <tr>
           <th>Column name</th>
           <th>Column description</th>
         </tr>
       </table>
       <button id="save" onclick="$page_graph.save_relation()">Save</button> <button id="cancel">cancel</button>
    </body>
</html>
