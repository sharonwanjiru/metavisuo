<?php
include_once "config.php";
//
class page_graph {
    //use mutall;
    
    function __constructor($dbname=null){
         //
         //bind the database name
         $this->bind_arg('dbname', $dbname);
         //
         //page graph has a database 
         $this->dbase= new database($this->dbname);
         //
         //Get the complete database structure
         $this->dbase->export_structure;
    }
}


//extends the column attribute
class myattribute extends column_attribute{
    //
    //
    function __constructor($cname, entity $parent){
        //
        //Set the name of the attribute
         parent::__construct($cname, $parent);
    }
    
}


//
class relation extends column_foreign{
    //
    //The class constructor
    function __construct($name, entity $parent, $table_name) {
        //
        //The parent constructor 
        parent::__construct($name, $parent, $table_name);
    }
}


class alterable_entity{
    
}