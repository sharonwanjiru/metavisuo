<?php
//
//Catch all errors, including warnings.
\set_error_handler(function ($errno, $errstr, $errfile, $errline /*, $errcontext*/) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});
//
//The schema is the base of all our applications; it is primarily used for
//supporting the database class
include_once $_SERVER['DOCUMENT_ROOT'] . '/schema/v/code/schema.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/schema/v/code/sql.php';

//.
//Construct a new database
$db = new mutall\database("mutall_imagery", true, false);
//
//
//Visualize the results as a JSON string
echo "<pre>" .$db->error_report  . "</pre>";

