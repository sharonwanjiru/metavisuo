<?php
    //Include the library 
    include 'library.php';
    //
    //Create a new database object 
    $dbase = new database($name='majomco', $username= 'mutallco', $password ='mutall_2015');
    //
    //Create an sql that describes table user 
    $sql= "SHOW FULL COLUMNS FROM `user` FROM `majomco` WHERE Field='password'";
    //
    //Run the query 
    $result = $dbase->query($sql);
    //
    //Put the data in an $array
    $array = $result->fetch(PDO::FETCH_ASSOC);
    //
    //
    //
    //
    //decode the array into a json
    var_dump($array);
         
     if ($array['Null']=='NO'){
         //
         //Change from a no to a not Null
         $array['Null']= 'Not Null';
     }
     else{
         //
         //
         $array['Null']= 'Null';
     }
     var_dump($array);
    //
    //Loop through;
    //
    //Encode the array inorder to use it in javascript //More comfortable in javascript 
  $json=json_encode($array);
   //
?>

<html>
    <head>
        <title> Graph</title>
        <link rel="stylesheet" type="text/css" href="graph.css">
        <script>
            const array= <?php echo "$json"; ?>;
            //
            //
            const column=array[0];
        </script>
    </head>
    <body>
    </body>
</html> 