
<html>
    <head>
        <title> testing</title>
        <link rel="stylesheet" type="text/css" href="graph.css">
    </head>
    <body>
        <script>
            async function test(){
                //
                //fetch the db names for the test 
                const response = fetch('test.php');
                const json = response.json();
                console.log(json);
            }
            test();
        </script>
    </body>
</html> 