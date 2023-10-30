<?php
session_start();
//
//Why is this a trait, rather than a class? I guess I was too lazy to change 
//from database extends PDO to database extends mutall
class mutall{
    //
    //The function that supporta executon of arbitray methods on arbitrary class
    //objects from Javascript
    static function index(){
        //
        //Test if the class name is provided by the user. If not, re-direct to the
        //default home page.
        if (!isset($_REQUEST['class'])){
            //
            //Class name not provided. Go to the default home page
            //Get the http that referered to this page
            $referer = $_SERVER['HTTP_REFERER'];
            //
            //Extract the url path
            $path = parse_url($referer, PHP_URL_PATH);
            //
            //Retrieve the directory
            $dir = pathinfo($path, PATHINFO_DIRNAME);
            //
            //Retrieve the basename,
            $basename = pathinfo($dir, PATHINFO_BASENAME);      
            //
            //The complete website directory 
            $location = "../$basename/$basename.php";
            //
            //Re-direct to the home page. (Note. Make sure tat there is no echoing
            //before a header is sent!!)
            header("Location: $location");
            exit();
        }
        //
        //The class was providec by the user. The method must also be provided; 
        //otherwise we report an error
        // 
        //Retrieve and set the classname from the url 
         $class=$_REQUEST['class'];
        //
        //Test if there is a method set 
        if(!isset($_REQUEST['method'])){
            //
            //METHOD NOT KNOWN 
            //
            //Die and throw an exception that a method must be set 
            throw new Exception('The method of the class to execute must be set');
        }
        //
        //METHOD KNOWN 
        //Retrieve and set the method from the query string 
        $method= $_REQUEST['method'];
        //
        //Determine if the desired method is static or not. By default it is dynanic,
        //i.e., not static
        if (isset($_REQUEST['static']) && $_REQUEST['static']){
            //
            //Execute the method and track its result. 
            $result = $class::$method();
        }
        else{
            //
            //Create an object of the class on assumption that the class is not static 
            $obj= new $class();
            //
            //Execute the methord ad track its result. 
            $result = $obj->$method();
        }
        //
        //This is the Expected result from the calling method
        return $result;
    }
    
    //
    function try_bind_arg($name, &$value){
        //
        //Check if the value is null
        if (is_null($value)){
            //
            //The value is empty.
            //
            //Get the named fprperty from the server global variables
            if (isset($_REQUEST[$name])){
                //
                //Retrieve the name value
                $value= $_REQUEST[$name];
            }
            //
            //Search in the session variables
            elseif (isset($_SESSION[$name])){
                //
                //Retrieve the name value
                $value= $_SESSION[$name];
            }
            else{
                return false;
            }
        }
        //
        //Set the named propertey to the argument value
        $this->$name = $value;
        //
        return true;
    }
    
    //
    function bind_arg($name, &$arg){
        //
        if (!$this->try_bind_arg( $name, $arg)){
            throw new Exception("Argument $name is not known");
        }
    }
    
    //Report exceptions in a more friendly fashion
    static function get_error($ex) {
        //
        //Replace the hash with a line break in teh terace message
        $trace = str_replace("#", "<br/>", $ex->getTraceAsString());
        //
        //Retirn the full message
        return $ex->getMessage() . "<br/>$trace";
    }
    //
    //sets the database access credentials as session keys to avoid passing them
    //any time we require the database object 
    static function save_session($username, $password){
        //
        //
        if(isset( $_SESSION['username'])){
        $_SESSION['username']= $username;
        }
        if(isset( $_SESSION['password'])){
        $_SESSION['password']= $password;
        }
    }
    
}

//This class models/represents a database
class database extends PDO{
    //Define the public properties name, size, zoom  of the database to present
    //passed by the constroctor 
    public $name;
    public $size;
    public $zoom;
    public $pan_right;
    public $pan_top;
    //
    //An array of entties
    public $entities=[];
    //
    //Create an empty skeleton, i.e., without entities, of the named database.
    function __construct($name=null, $username=null, $password=null){
        //
        //Get the arguments required from the user request request
        $this->bind_arg('name', $name);
        //
        //
        //Test if the user has supplie the login credentials explicitly
        $found = $this->try_bind_arg('password', $password)
                 && $this->try_bind_arg('username', $username);
        //
        if (!$found){
            //
            //User has not provided login credians and they are not found in the
            //session variables, so, get them from the config
            //
            //Set the login credebtials from the config file
            $username = config::username;
            $password = config::password;
            
            $this->password = $password;
            $this->username = $username;
        }
        
        //
        //Open a $database
        //
        //Specify the database name string
        $dbname = "mysql:host=localhost;dbname=$name";
        //
        //1.2 Inheriting from the parent PDO
        parent::__construct($dbname, $username, $password);
        //
        //1.3 Please throw exception on database errors, rather thn returning
        //false -- which can be tedious to looke for teh errors 
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    //Update the entities' coordinates to on this database. The structure of a
    //teh coordinates is:-
    //[{sql}, ...]
    function update_entity_coordinates($sqls=null){
        //
        $this->bind_arg('sqls', $sqls);
        //
        //Decode the coordinates to a php version
        $this->sqls = json_decode($sqls);
        //
        //loop through the coodinates array and update the corodinates at the table column 
        //using the sql alter command.
        foreach ($this->sqls as $sql){
            //
            //execute the query without assigning any variable since there is nothing to return 
             $this->query($sql);
        }
        return true;
    }
    //
    //Returns data after executing the given sql on this database
    function get_sql_data($sql=null){
        //
        //Bind the sql statement and database name
        $this->bind_arg('sql', $sql);
        //
        //Query the database using the given sql
        $results = $this->query($sql);
        //
        //Fetch all the data from the database -- indexed by the column name
        $data = $results->fetchAll(PDO::FETCH_ASSOC);
        //
        //Return the fetched data                
        return $data;
    }
    
    //Overrding teh query method so that it can be evoked from JS. We use this
    //qiery method for sqls that dont return a result
    function query(string $sql, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false{
        //
        //Bind the sql statement and database name
        $this->bind_arg('sql', $sql);
        //
        //Query the database using the gven sql
        return parent::query($sql);
    }
    //
    //Returns a checked sql WHEN A QUERY HAS NO PARAMETERS!!!
    public function chk($sql) {
        //
        //A prepared pdo statment throws no exception even if it has errors
        $stmt = parent::prepare($sql);
        //
        //We have to executr the query for pdo to throw exception if the
        //prepared statement has errors
        //
        try {
            //
            //This is the reason why theis version works only with queries
            //without paramaters
            $stmt->execute();
            //
            $stmt->closeCursor();
            //
        } catch (\Exception $ex) {
            //
            throw new \Exception($ex->getMessage());
        }
        //
        //Return the same sql as the input
        return $sql;
    }
    
    //Offload the properties from the source to the destination
    static function offload_properties($dest, $src){
        //
        // throuhg all the proprties of the source and each property to the
        //destination if it does not exist
        foreach($src as $key=>$value){
            //
            if (!isset($dest->$key)){
                $dest->$key = $value;
            }
            
        }
        return $dest;
    }
    //
   //Return all the entities of this database by querying the information schema
    function get_entities(){
        //
        //Start with a empty list of entities
        $entities = [];
        //
        //Let $sql be the statement for for retrieving the entities of this
        //database.
        $sql = "select "
            //    
            . "table_name as name, "
            //    
            . "table_comment as comment "
        . "from "
            . "information_schema.tables "
        . "where "
            . "table_schema = '$this->name' "
            //
            //Exclude the views
            . "and table_type = 'BASE TABLE'";
        //
        //Execute the $sql on the the schema to get the $result
        $result = $this->query($sql);
        //
        //Retrueve the entires from the $result as an array
        $array = $result->fetchAll(PDO::FETCH_ASSOC);
        //
        //Display the $result by looping throuh the array to presebt each entity
            foreach($array as $row){
            //
            //Get the name of the entity; its the sane as that of the table.
            $name = $row['name'];
            //
            //Get the comment (json) string
            $comment_str = $row['comment'];
            //
            //Test if the comment is empty; if it is make it an empty json 
            if (empty($comment_str)){
                $comment_str= '{}';
            }else{
                $comment_str = $comment_str; 
            }
            //
            //Decode the comment json string to a php (stdClass) object. Rember
            //to include the opening and losing curly braces
            $comment = json_decode($comment_str);
            //
            //Throw an error message in case $comment is not in the json format
            if (!$comment){
                throw new Exception("Error in entity $name. Invalid json $comment_str"); 
            }
            //
            //Create a new entity passing the name, parent and the partially 
            //complete raw comment
            $entity = new entity($name, $this,$comment );
            //
            //Push the entity object to the array to be returned
            $entities[$name] = $entity;
        }
        //
        //Return the entity list.
        return $entities;
    }
    //
    //Returns a jason string if this database structure 
    function __toString() {
        //
        //Encoding the database structure to a json as a $jdbase(json dbase)
        $jdbase= json_encode($this);
        //
        //Throw an exception if $jbase was not properly encoded
        if(!$jdbase){
            return "Error in json encoding a database"; 
        }
        //
        //Return the string json vertion of the database structure
        return $jdbase;
    }
    //
    //Returns this database's complete structure, i.e., one that has entities
    //in it. 
    function export_structure(){
        //
        //Fill the database skeleton with entities derived from table comments
        //of the information schema database 
        $this->entities = $this->get_entities();
        //
        //save this database in the session 
        $_SESSION[$this->name]= serialize($this->entities);
        //
        //Return this structure 
        return  $this;
    }
    
    //
    //popilates the database from a static json structure 
    function populate($dbase_){
        //
        //Decode the json structure
        $static_dbase= json_decode($dbase_);
        //
        //offload the entities from the static dbase to this class
        //Get the static entities
        $static_entities= get_object_vars($static_dbase->entities);
        //
        //Activate each entity by creating an instance of root entity and offloading
        //all the properties it has
        foreach ($static_entities as $key=>$entity_){
            //
            //push the activated entity into the entities collection of this database 
            $this->entities[$key]= $this->activate_entity($entity_);
        }
    }
    
    //
    //returns an activated entity with all its properties
    function activate_entity($entity_){
        //
        //Create an entity passing the static strucure to be offloaded its 
        //properties
        $entity= new entity($entity_->name, $this, $entity_->comment,$entity_);
        //
        //offload all the properties of the static entity
        database::offload_properties($entity, $entity_);
        //
        //Retutn a fully popilated entity 
        return $entity;
    }



    //Executes the given (insert) sql and returns the last inserted iid of the
    //record 
    function save_record($sql=null){
        //
        //Bind the sql variable.
        $this->bind_arg('sql', $sql);
        //
        //Query the database. I hope that if theer is a problem the query will
        //throw an exception
        $this->query($sql);
        //
        //Assuming success, retun the last id
        return PDO::lastInsertId();
    }
    
    //Use a transaction to save the given inputs generated by some service to 
    //this database. The input has 2 objects: inserts and updates; both have 
    //the following format:-
    //  [{ename, values:[{fname, value}, ...]}, ...] which are self explanatory
    function save_service($inputs=null){
        //
        //Bind the given sql data
        $this->bind_arg('inputs', $inputs);
        //
        //Convert inputs from json to PHP
        $this->inputs = json_decode($inputs);
        //
        //Debugging
        //throw new Exception(print_r($inputs, true));
        //
        //Save inputs in 2 steps, i.e., insert and update to avpid possiblility 
        //of endless loops. Insert is not necessar if teh entit record already
        //existe in the database
        $this->beginTransaction();
        //
        try{
            //Use a variation/extension of the original inputs where the entries 
            //are now indexed by the entity name. It will have the format:-
            //[{ename, select, insert, update, primarykey}, ....] where:-
            //select, insert and update are CRUD sqls generated during this process
            //They are are saved here for debugging purposes. If a record exists
            //or an insert is successful the primary key is updated
            $this->inputs_ex = [];
            //
            //Execute the insert statements (if necessary) for all the entities 
            //to be associated with primary keys, updating the extension.
            $this->insert_entities($this->inputs->inserts);
            //
            //Debug
            //throw new Exception("<pre>".print_r($this->inputs, true)."</pre>");
            //
            //Then updated all the entities (now existing in database). 
            $this->update_entities($this->inputs->updates);
            //
            //Commit the changes as this was a succesful operation
            $this->commit();
        }
        //Roll back the changes if there is any problem
        catch(Exception $ex){
            $this->rollBack();
            //
            //At this point yu can inspect $this->input_ex to see what happened
            throw $ex;
        }
    }
    
     //Use the insert data from the orinal input (obtained from JS) to formulate
    //and execute insert statements for entity records that don't exist in the 
    //datanase yet; then update the shared inputs extension.
    //The inserts structure is a (non-indexed) array:-
    //[{ename, values}, ...] where values is {fname, value} and value is 
    //either {type, text} where type is either attribute or foreign and 
    //text is either a string literal for attributes or a entity name for
    //foreign keys
    function insert_entities($inserts){
        //
        //Debug
        //throw new Exception(print_r($inserts, true));
        //
        //For every entity in the user inputs generate an select sql to test if
        //the record exsts; if it does not generated an insert sql, execute and 
        //save in the inputs extension. The extension is needed for holding 
        //primary key values and debugging/error reporting
        foreach($inserts as $insert){
            //
            //Define the extension object
            $ex  = new stdClass();
            //
            //Get the entity name
            $ename = $insert->ename;
            //
            //Save the extension, indexed by entity name
            $this->inputs_ex[$ename]= $ex;
            //
            //Formulate the select sql and save it in the extension. It has the form 
            //select entity from entity where ....
            $ex->select = $this->get_sql_select($ename, $insert->values);
            //
            //Query and fetch the data if any
            $query = $this->query($ex->select);
            $found = $query->fetch();
            //
            //Test if the entity record is found
            if ($found){
                //
                //Yes a record exist. Retrieve the first column of the (hopefully)
                //only record retrieved and save it in the extension
                $ex->primarykey = $found[0][0];
            }
            else{
                //
                //Record not found. Insert it
                //
                //Formulate the insert sql statement
                $ex->insert = $this->get_sql_insert($ename, $insert->values);
                //
                //Execute the sql statement.
                $this->query($ex->insert);
                //
                //Save the primary key of the last insert entity to support saving 
                //other higher dependency entities
                $ex->primarykey = PDO::lastInsertId();
            }
        }
    }
    
    //Compile the insert sql. The named values has the structure:-
    //[{fname, value}, ...] where value is {type, text}. Type is either the
    //'attrubute' or 'foreign' key word and text is a string litereral whuch means
    //the attribute value or foreign key reference table name
    function get_sql_insert($ename, $nvalues){
        //
        //Collect the comma separated field names
        $cnames = array_map(function($nvalue){return $nvalue->fname;}, $nvalues);
        $columns = implode(", ", $cnames);
        //
        //Collect the comma separated field values
        $values2 = array_map(function($nvalue_in){
            //
            //Simplify the input value value
            return $this->evaluate($nvalue_in->value);
        }, $nvalues);
        //
        $values3 = implode(", ", $values2);
        //
        //Compile the insert sql statement
        return "insert into $ename ($columns) values($values3)";
    }
    
    //Simplify the given input value, structured as, {type,text} to a simple 
    //text or primary key integer. For instance:-
    // {'attribute', 'Hello word'} simmplifies to 'Hello word'
    // {'foreign', 'client'} simplifies to 5, where 5 is the primary key  value 
    // of the current client record. The property, $this->input_ex, is an indexed
    // array that tracks the primary keys for each entity.
    function evaluate($value_in){
        //
        //Depending on the input value type....
        switch($value_in->type){
            //
            //An attribute simplifies to a simple text or null
            case 'attribute': 
                //
                //Remove leading and trailing spaces
                $value1 = trim($value_in->text);
                //
                //Test for emptiness, returning a null if it is
                $value_out = $value1=='' ? 'null' : "'". $value1. "'";
                break;
            //
            //A foreign (key) value needds to be looked up inptut extension. It 
            //must be found in the extension -- unless the sort by dependency
            //has failed
            case 'foreign':
                //
                //Get the foreign key reference table name
                $ref_ename = $value_in->text;
                //
                //Look up the primary key of this entity in the input extension, 
                //assumimg that the inserts were sorted by order of dependency.
                if (
                    isset($this->inputs_ex[$ref_ename])  
                    && isset($this->inputs_ex[$ref_ename]->primarykey)
                ){
                    //
                    $value1 = $this->inputs_ex[$ref_ename]->primarykey;
                    $value_out = "'". $value1. "'";
                   //
                }
                //Primary key not found; return a null
                else{
                    $value_out = 'null';
                }
                break;
            default:
                throw new Exception("Input value type $value_in->type is not known");
        }
        //Return the quote enclosed value
        return $value_out;
    }

    //Formulate and execute the update sql statement for each entity using the 
    //given data. See insert_entoites to see the structire of updates.
    function update_entities($updates){
        //
        //For every entity in the sql data, generate and exceute an update sql --
        //if necessary
        foreach($updates as $update){
            //
            $ename = $update->ename;
            //
            //Prepare to collect a valid sql
            $sql=null;
            //
            //Formulate the update sql statement (and save it in the extemsion)
            $ok = $this->try_sql_update($ename, $update->values, $sql);
            //
            //Only valid sqls are considered.
            if ($ok){
                //
                //Save the sql (for debugging purposes)
                $this->inputs_ex[$ename]=$sql;
                //
                //Execute the sql statement.
                $this->query($sql);
            }
        }
    }
    
    //Compile the update sql statement. The named values have teh structure:- 
    //[{fname, value}, ...] 
    function try_sql_update($ename, $nvalues, &$sql){
        //
        //Add the evaluated result to the named value version
        foreach($nvalues as $nvalue){
            //
            $nvalue->result = $this->evaluate($nvalue->value);
        }
        //
        //Filter out the cases where the evaluation result is null
        $filtered_nvalues = array_filter($nvalues, function($nvalue){
            return $nvalue->value!='null';
        });
        //
        //If there are no fietered values, then an upate is not required
        if (empty($filtered_nvalues)) {return false; };
        //
        //Otherwise return a valid sql.
        //
        //Map the field  name/values pairs to a comma separated list of assignments
        $sets0 = array_map(function($nvalue){
            //
            $value = $this->evaluate($nvalue->value);
            //
            //What if the value is null? Ignore it.
            //
            //Remember to quote the field names; the values are already quoted
            return "`$nvalue->fname` = $value";
        }, $filtered_nvalues);
        $sets = implode(", ", $sets0);
        //
        //Retrieve the primary key of the current entity. It must exist
        $primarykey = $this->inputs_ex[$ename]->primarykey;
        // 
        //Compile the update sql statement
        $sql = "update $ename set $sets where $ename=$primarykey";
        //
        //The sql is valid.
        return true;
    }
    
    //Compile the update sql statement. The named values are [{fname, ename}, ...] 
    function get_sql_select($ename, $nvalues){
        //
        //Map the field  name/values pairs comma separated list of assignments
        $conditions0 = array_map(function($nvalue){
            //
            $value = $this->evaluate($nvalue->value);
            //
            //Set teh quality, depending on whether we are dealing with a nul or not
            $equal = $value=='null'? 'is': '=';
            //
            //Remember to quote the field names; the values are already quoted
            $assign = "`$nvalue->fname` $equal $value";
            //
            return $assign;
        }, $nvalues);
        $conditions = implode(" AND ", $conditions0);
        //
        //Compile the update sql statement
        return "select `$ename` from `$ename` where $conditions";
    }
}
//Class that represents an entity
class entity {
    //
    //Name of the entity and the coodinates of the entity
    public $name;
    public $cy;
    public $cx;
    public $color;
    //
    //The json user information retrieved from the comment after it was decoded  
    public $comment;
    //
    //the parent class database protected to avoid recursion in json.
    protected $dbase;
    //
    //Defining the instance of a child class column that feed the entity with more 
    //properties popilated by the function get column()
    public $columns=[];
    //
    //Defining the array $induces that is used to store the indexed columns 
    //from the function get_induces 
    public $indices=[];
    //
    //This entity's relational dependency.
    public $dependency_ = null;
    //
    //Define the methods in the sql interface
    //
    // The entity constructor requires:-
    // a) the entity name 
    // b) the parent database and 
    // c) the partially complete comment as a json text. 
    function __construct($name, database $parent, $comment, $entity_=null){
        //
        //initialize the name, parent database, json comment,column list in 
        //array format and the ccodinates the cx and the cy.
        $this->name=$name;
        $this->dbase= $parent;
        $this->comment = $comment;
        $this->dbname = $parent->name;
        //
        //Test if the columns_ are null if true activate then activate them else 
        //derive the columns from first pronciple 
        //there ara no static columns derive from first principle 
        if(is_null($entity_)){
            //
            //Set the the columms property from the informaton schema. The columns
            //will be either primary or not.
            $this->columns = $this->get_columns();
            //
            //Update the relevent ordinary columns to foreign columns using the 
            //referential constraits as guided by the key uage table.
            $this->update_columns();
            //
            //Add the indentification indices
           $this->indices = $this->get_indices();
        }
        //
        //derive the columns from the static structure
        else {
            //
            $this->activate_columns($entity_);
        }
        
    }
    
    function __sleep(){
        return ['name', 'cx', 'cy','color', 'columns', 'indices', 'comment', 'dependency_', 'dbname'];
    }
    
    function __wakeup(){
        $this->dbase[$this->dbname]; 
    }
    
    
    //
    //Activates the columns from a static structure bearing in mind their respectivev 
    //types primary, foreign and the attributes 
    function activate_columns($static){
        //
        //Get the static columns inorder to activate them
        $columns_= get_object_vars($static->columns);
        //
        //Loop through the array creating the various array types based on the 
        //type
        foreach ($columns_ as$key=> $column){
            switch ($column->type) {
                case 'attribute':
                    //
                    //create an attribute and push it in the columns array property
                    $attribute=new column_attribute($column->name, $this);
                    //
                    //ofload any of its properties 
                    database::offload_properties($attribute, $column);
                    $this->columns[$key]= $attribute;
                    break;
                case 'foreign':
                    //
                    //Create a foreeign and push it in the columns property
                    $foreign= new column_foreign($column->name, $this, $column->ref_table_name);
                    //
                    //ofload any of its properties 
                    database::offload_properties($foreign, $column);
                    $this->columns[$key]=$foreign;
                    break;
                case 'primary':
                    //
                    //Create a primary an push it to the columns
                    $primary= new column_primary($column->name, $this);
                    //
                    //ofload any of its properties 
                    database::offload_properties($primary, $column);
                    $this->columns[$key]= $primary;
                    break;
            }
        }
        //
        //Set the indices
        $this->indices=(array)$static->indices;
    }


    //
    //Return the dbase property to a public access modification  
    function __get($name){
        switch($name){
            case 'dbase': return $this->dbase;
            default:throw new Exception("Unusual! Property $name not found.");    
        }
    }
     //Returns the relational dependency of this entity based on foreign keys
     function get_dependency(){
        //
        //Test if we already know the dependency. If we do just return it...
        if (!is_null($this->dependency_)){ return $this->dependency_;}
        //
        //...otherwise calculate it from 1st principles.
        //Some of the entities do not have idices hence if the indices array is 
        //empty return a null
        if(empty($this->indices)){
            return null;
        }
        //
        //Select the first index's colun names
        $ixcnames = array_values($this->indices)[0];
        // 
        //Let $cols be the array to store the indexed column foreigns 
        $cols=[];
        //Get the foreign key column names used for identification.
        foreach ( $ixcnames as $cname) {
            //
            //Get the column 
            $col= $this->columns[$cname];
            //
            //Test if the column is foreign and if true push it into the array
            if($col instanceof column_foreign){
            array_push($cols, $col);
            }
        }
        //
        //Test if there are no foreign key columns, return 0.
        if(sizeof($cols)==0){
            $this->dependency_=0;
        }
        //
        //There are foreign keys as indexes
        else{
            //Map cname's entity with its dependency.
            $cols_depths=array_map(array($this,'map_dependency'), $cols);
            //
            //Get the foreign key entity with the maximum dependency, x
            $max_dependency = max($cols_depths);
            //
            //Set the dependency
            $this->dependency_ = $max_dependency + 1;
        }
        //
        //The dependency to return is x+1
        return $this->dependency_;
    }
    //
    //Returns the dependency of the referenced column
    function map_dependency($col){
        //
        //Get the referenced entity name
        $ename= $col->ref_table_name;
        //
        //Get the actual entity
        $entity = $this->dbase->entities[$ename];
        //
        //Get the referenced entity's dependency.
        return $entity->get_dependency();
    }


    //
    //Sets cx coordinate of this entity. 
    function get_cx(){
        //
        //Returns a random value of coordinate cx is not set by the user 
        if(!isset($this->comment->cx)){
          $cx=rand(0, $this->dbase->size);
        }else{
           $cx=  $this->comment->cx; 
        }
        
        return $cx;
    }
    //
    //The function that assigns the cy coodinate
    function get_cy(){
        
        //Returns a random value of the cy if not set by user(randomly set)
        if(!isset($this->comment->cy)){
            $cy=rand(0, $this->dbase->size);
        } else {
          $cy= $this->comment->cy;  
        }
        return $cy;
    }
    //
    //Gets he sql of this entity and executes if for display 
    function review(){
        //
        //Get the depencency of this entity
        $this->dependency= $this->get_dependency();
        //
        //Ourputs html code for disolaying the data retrieved by this sql
        $this->show($this->dbase);
    }
    //
    //Compute and return the color of this entity. 
    function get_color(){
       //
       $color = 'yellow';
       //
       //Test if this entity has any properties by pushing properties into an array
       $array = get_object_vars($this);
       //
       //counting all the components in the array
       $count = count($array);
       //
       //Return red for a new entity, i..e, one where this entity has no 
       //properties
       if ($count<2){
           $color = 'red';
       }
       //
       //Return the computer color
       return $color;
   }
    //
    //Returns the entity to be presented as boolean of true or false
    function visible(){
       if(isset($this->comment->remove)){
           return FALSE;
       } else {
            return TRUE; 
       }  
   }
   //
   //Returns the parent 
   function  get_parent(){return $this->dbase;}
    //
    //Returns the columns of this entity from the information schema 
    function get_columns(){
        //
        //Start with an empty list of columns
         $columns = [];
         //
        //Select the columns of this entity from the database's information schema
        $sql = "select "
            //
            //Shorten the column name
            . "column_name as name, "
            //
            //Specifying the type of data in that column
            . "data_type, "
             //
            //The column key so as to identify the primary keys
            . "column_key as `key`, "
                //
                //Get the default 
                    . "column_default as `Default`,"
                //
                //if it is nullable
                    . "is_nullable,"
              //
            //Extract any meta data json information in the comments
            . "column_comment as comment "
        . "from "
            //
            //The main driver of this query
            . "information_schema.`columns` "
        . "where "
            //    
            // The table schema is the name of the database
            . "table_schema = '{$this->dbase->name}' "
            //
            //specifying the exact table to get the column from
            . "and table_name = '{$this->name}'";
        //
        //2. Execute the $sql on columns to get the $result
        $result = $this->dbase->query($sql);
        //
        //put the data in an $array
        $array = $result->fetchAll(PDO::FETCH_ASSOC);
        //
        //Loop throuh each row of the array to create a new column for each row
        //as a stdClass object
        foreach($array as $row){
            //
            //Name of the column
            $name = $row['name'];
            //
            //Get the comment (json) string
            $comment_str = $row['comment'];
            //
            //Test if the comment is empty; if it is make it an empty json 
            if (empty($comment_str)){
                $comment_str= '{}';
            }else{
                $comment_str = $comment_str; 
            }
            //
            //Decode the comment json string to a php (stdClass) object. Rember
            //to include the opening and losing curly braces
            $comment = json_decode($comment_str);
            //
            //Throw an error message in case $comment is not in the json format
            if (!$comment){
                throw new Exception("Error in entity $this->name column $name. Invalid json $comment_str"); 
            }
            $row['comment']=$comment;
            //
            //Create a new column and assign it a name
            $column = $this->create_new_column($name, $row);
            //
            //Add the column to the array to be returned
            $columns[$name]= $column; 
        }
        //        
         return $columns;        
     }
     //
     //Returns the columns as either ordinary or primary 
     function create_new_column($name,$row) {
        //
        // Return the column as primary if its key is set to PRI
         if (isset($row['key'])&& $row['key']=='PRI'){
             //
             //Createa primary key column
             $column= new column_primary($name, $this);
         }
         //
         //Create an ordinary column 
        else {
            $column=new column_attribute($name, $this);
        }
        //
        //Offload the remaining properties from $row
        database::offload_properties($column, $row);
        //
        //Remove the key property from the column
        unset($column->key);
        //
        //
        //Return the created column
        return $column;
     }
    
    //Update some ordinary columns to foreign columns base on the key column 
    //usage table
    function update_columns(){
        //
        //Set sql statement for selecting all foreign key columns of this table 
        //and database
        $sql = "select "
            //
            //Shorten the column name
            . "column_name as name, "
            //
            //Specify the referenced table
            . "referenced_table_name as tname "
        . "from "
            //
            //The main driver of this query
            . "information_schema.key_column_usage "
        . "where "
            //    
            // The table schema is the name of the database
            . "referenced_table_schema = '{$this->dbase->name}' "
            //
            //specifying the exact table to get the column from
            . "and table_name = '{$this->name}'";
            
        //
        //Execute the $sql on columns to get the $result
        $result = $this->dbase->query($sql);
        //
        //Put the resulting data in an $array
        $array = $result->fetchAll(PDO::FETCH_ASSOC);
        //
        //looping through the result to compare the row name 
        foreach ($array as $row){
            //
            //The foreign key column name
            $name = $row['name'];
            //
            //Get the matching column from this entity
            $match= $this->columns[$name];
            //
            //Create a foreign key colum using the same name
            $foreign = new column_foreign($name, $this, $row['tname']);
            //
            //Offload all properties of the ordinary column to the foreign key 
            //version
            database::offload_properties($foreign, $match);
            //
            //Set the foreign key.
            $this->columns[$name] = $foreign;
           }
           
    }
    //
    //Get the indexwd column and return then in an array 
    function get_indices(){
        //
        //Initialize an empty array indices to store the index columns
        $indices=[];
        //
        //let sql be the select query that obtains the indexed columns 
        //from the table statistics
        $sql = "select "
            //
            //Shorten the column name
            . "column_name as cname, "
            //
            //specify the index name e.g id
            . "index_name as xname "
        . "from "
            //
            //The main driver of this query
            . "information_schema.statistics "
        . "where "
            //    
            // The table schema is the name of the database
            . "index_schema = '{$this->dbase->name}' "
            //
            //specifying the exact table to get the column from
            . "and table_name = '{$this->name}'"
               // 
                //Only get the index id e.g. id2
            . "and index_name like 'id%'";   
        //
        //Execute the $sql on the schema to get the $result
        $result = $this->dbase->query($sql);
        //
        //Retrieve columns from the $result as an array
        $array = $result->fetchAll(PDO::FETCH_ASSOC);
        //
        //loop through each row to get the indexed columns of type id 
        foreach($array as $row){
            //
            //Get the column name of the indexed columns.
            $cname = $row['cname'];  
            //
            //Get the index name of the column 
            $xname =$row['xname'];
            //
            //storing the index in an array 
            $indixed_column[$xname][]=$cname;
            //
            //Pushn the two in a double array 
            $indices=$indixed_column;

        }
        return $indices;
    }
   
    //
    //Present this entity as an elipse
    function present(){
        //
        //Show this entity only if it is not ignored.
        if ($this->visible()){
            //
            //
            //Draw the elipse to represent the entity
            echo "<g>"
            . "<ellipse " 
                ."cx='$this->cx'" 
                ."cy='$this->cy'" 
                ."rx='150'" 
                ."ry='50'"
                ."style='fill:$this->color'" 
                ."id='$this->name'"
                .'onclick="$page_graph.select(this)"'
                . 'class="draggable"'
            ."/>"
                
            . " <text " 
                     ."x='$this->cx'" 
                     ."y='$this->cy'"
                     ."text-anchor='middle'"    
                     ."fill='white'>$this->name"
                ."</text>"
           ."<g>"; 
            //
            //Draw the ;ines to represent the relations starting from this entity
            foreach($this->columns as $column){
                $column->present_relation();
            }      
        }
        
    }
    
}
//
// Class column that popilates all the entity column from the database information 
//schema table column of a given table name it is an abstract class hence no bjects can be made from it 
 class column{
     const attribute = "attribute";
      const foreign = "foreign";

     //Every column should have a name 
    public $name;
    //
    //????
    protected $entity;
    //
    //The class constructor
    function __construct($name, $type, entity $parent) {
        $this->entity=$parent; 
        $this->type=$type;
        $this->name = $name;
    }

    //By default, show nothing as a relation for an ordinary column.
    function present_relation(){
        //
    }
    //
    //Get the full description of the fname as entity.fname
    public function to_str() {
        //
        //The full name has an entity name(table name)and a field name
        //
        //Get the name of the field
        $fname= $this->name;
        //
        //Get the name of the entity 
        $ename= $this->entity->name;
        //
        //Return the full description as ename.fname
        $full= "\t $ename.$fname";
        //
        return $full;
    }
    //
    //since i can not acess a protected property
    function get_parent(){
        return $this->entity;
    }
}
//
// This is the class that contains all the columns that are primary indexed keys 
//as obtained from the query 
class column_primary extends column{
    //
    //The class constructor 
    function __construct($name, entity $parent) {
        //
        parent::__construct($name, "primary", $parent);
    }
    //
     //display the value of its sql as uneditable
     function show($value){
         return "<td onclick='record.select_td(this,$value)' title='$value'"
                    . "type='$this->type' name='$this->name'> $value"
                ."</td>";
     }
}
//
//Template for the second type of column called columns attribute
class column_attribute extends column{
    //
    //contains all the columns of type attribute 
     function __construct($name, entity $parent) {
         //
         //The parent constructor
         parent::__construct($name, "attribute", $parent);
         
     }
     //
     //display the value of its sql in an editable format
     function show($value){
         return  "<td onclick='record.select_td(this)'title='$value'"
                    . "type='$this->type' name='$this->name'> $value"
                ."</td>";
     }
}
//
//This is the class that has all the columns which are pointers to other entities 
class column_foreign extends column{
    //
    //the name of the referenced table 
    public $ref_table_name;
    //
    //The class constructor
    function __construct($name, entity $parent,$table_name) {
        //
        //The parent constructor 
        parent::__construct($name, "foreign", $parent);
        //
        //Initializing the variables in the constructor
        $this->ref_table_name=$table_name;
        $this->name=$name;
        //$this->type= $this->set_type();
    }
    //
    //Relation are presented by using this entity as the start entity and the 
    //ref_table_name as the name of the end entity  
    function present_relation(){
       //
       //Relations require a start and an end
       //
       //Let the start be this entity
       $start = $this->entity;
       //
       //Get the global dbase
       $dbase = $this->entity->dbase;
       //
       //Let the end be the entity named by the reference_table_name 
       $end = $dbase->entities[$this->ref_table_name];
        //
        //Display the relations inform of a line 
        echo "
            <line
                x1='$start->cx'
                y1='$start->cy'
                x2='$end->cx'
                y2='$end->cy'
                style ='fill: yellow'
                id='$start->name to $end->name'
                stroke:black;
                stroke-width:3;
            />";         
    }
    //
    //Sets the type of the relation as either an is_{a or a has_a 
    function set_type(){
       if(isset($this->comment->type)){
           $this->type= $this->comment->type->type;
       } 
        else {
           $this->type='has_a';
         }
    }
    
     //
     //display the value of its sql as a button with the value is its ids
     function show($value){
         //
         $arr = json_decode($value);
         if (json_last_error()){
             return json_last_error_msg();
         }
         //
         $primary = $arr[0];
         $friendly=$arr[1];
         //
         //return the a span so as to set the ref_table_name as an attribute
         return "<td onclick='record.select_td(this)'title='$value'"
             . "ref='$this->ref_table_name'id='{$primary}_{$this->ref_table_name}'"
                    . "type='$this->type' name='$this->name'>"
                     . " $friendly"
                ."</td>";
     
     }
     //
     //Display a button
     
     
}
