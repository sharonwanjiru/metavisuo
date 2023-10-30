<html>
    <head>
        <title>login</title>
        <style>
            label{
                display:block;
            }
        </style>
        <script>
            //
            //Save username to the windows object so that we can access it from
            //wherever we opened this window
            function save_username(){
                //Get the username
                let $username =document.querySelector("[name='username']").value;
                let $password =document.querySelector("[name='password']").value;
                //
                //Save the username and password so as to use them as properties 
                //of the page graph
                window.username=$username;
                window.password=$password;
            }
        </script>
    </head>
    
    <body>
        
        Log in
        <form method="post" action="index_1.php">
            <label>Username <input type="text" name="username"/></label>

            <label>Password <input type="text" name="password"/></label>

            <input type="submit" onclick="save_username()"/>
        </form>
        
    </body>
</html>