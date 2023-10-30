<?php
//
//the file where the sql is defined 
include 'library_sql.php';
//
//this update is also in the sql namespace 
namespace sql;
//
//this class updates the sql table as its root 
class update extends table{
    //
    //
    function __construct(\entity $e) {
        parent::__construct($e);
    }
    //
    //
    
}



