<?php
//
//require the library from where the session variables are destroyed
require 'library.php';
    //
    //destroy the sessions 
    session_destroy();
    //
    //unset the session variables 
    unset ($_SESSION);
    //
    //output message 
    echo "logout sucessfully";


