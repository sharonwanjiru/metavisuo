<?php
namespace sql;
//
//This class was created to make usable the root entity without the sql libray 
//it treats the entity as a simple table sql that is why the table extends an sql 
//and yet it represents a root entity; for it to be able to select anything from 
//it override the select property with the fields required as an aray 
class table extends sql{
    //
    //this class requires the root entity which as the source 
    public  $entity;
    //
    //The table has a select with all its columns as the fields required for selection 
    public $select;
    //
    //The fields of a table are an array of expression from which the select of 
    //a table are created 
    public $fields;
    //
    function __construct(\entity $e) {
        //
        //save the entity
        $this->entity= $e;
        //
        //For esier acces to the name of this table also save it 
        $this->name= $e->name;
        //
        //The fieelds of a table for now are the column maped to create expressions 
        //of type column 
        //$this->fields= array_map(function($col){return new column($col)};, $this->entity->columns);
        //
        //To select the fields from an a table with the columns as the array of fields 
        $this->select= new select($this, $fields, [], []);
        //
        //the parent constructor called with a root sql, array joins and array wheres
        parent::__construct($this, [], []);
    }


    //The from expression of an entitiy is its name 
    function fromexp(){return "`$this->name`"; }
    
}
//
//This class models the basic outlook of an sql not specific to any sql only models 
//the common aspects of the sqls which are
//1.The root which is used to formulate the from expression
//2.The joins Which are the conection of the various fields suplied 
//3. wheres, the criteria that is required for the data  
//4.allias 
 class sql{
     //
    //the criteria of extracting information from an entity is null since currently 
    //we are retrieving everything from an entity
    public $where;
    public $orderby;
    //
    //Get the joins required for this sql for now return a null since the joins 
    //are not yet developed 
    public $joins;
    
    //
    //Setting the components of the sql
   function __construct(sql $root,  array $joins,array $wheres, $alias=null ){
     
        $this->root= $root;
        $this->joins= new joins($joins);
        $this->where= new wheres($wheres);
        $this->allias= $alias;
    }
    //
    //returns the root of this function as a string that can be places in an sql query 
    function fromexp(){
        return 
        
        "({$this->to_str()})as {$this->elias}";
    }
    //
    //Executes the sql
    function query(\database $dbase){
        //
        //Get the sql string 
        $sql=$this->to_str();
        //
        // Execute the $sql on columns to get the $result
        $result = $dbase->query($sql);
        //
        //Get all the requested records 
        $array = $result->fetchAll(\PDO::FETCH_ASSOC);
        //
        return $array;
    }
    
    function show($dbase){
        //
        //Get the fields 
        $fields= $this->fields->get_array();
        //
        //Execute this sql 
        $array= $this->query($dbase);
        //
        //Ouptut a table
        echo "<table id='fields'>";
        echo $this->header();
        //
        //Loop through the array and display the results in a table 
        foreach ($array as $row) {
            //
            $id=$row[$this->name];
            echo "<tr onclick='review.select(this)' id='$id'>";
            //
            //Step through the columns
            foreach($fields as $field){
                //
                //Get the indexes of the field
                $name= is_null($field->alias) ? $field->to_str():$field->alias;
                //
                //Get the field value
                $value = $row[$name];
                
                echo $field->show($value);
                
            }
            
            echo "</tr>";
        }
        echo "</table>";
        
        
    }
    //
    //returns the title heads of the various fields 
    function header() {
        //
        //Get the fields in this sql 
        $cols= $this->fields->get_array();
        //
        //Loop through the fields and display the <tds>
        foreach ($cols as $col) {
            //
             $name= is_null($col->alias) ? $col->to_str():$col->alias;
            echo "<th>$name</th>";  
        }
    }
}

//
//models the sql of type select it extends an sql it requires an array of fields intented 
//to be retrieved
class select extends sql{
    //
    //This is the unique identification of the root of type identifier from where 
    //joins result from
    public $identifier;
    //
    //An array object with all the fields that are required to be retrieved
    //it requires an array of expressions 
    public $fields;
    //
    //The root from where the the other components are derived from 
    public  $entity;
    //
    //
    function __construct(table $t, array $fields, array $joins, array $wheres, $alias=null) {
        //
        //The root of the select is an sql table 
        $this->entity =$t->entity;
        //
        //The root must be an sql hence nesesitated the creation of the /sql table 
        //Which is the sql representation of the root entity 
        $this->root= $t;
        //
        //The fields of this class passed as an array must also be an array object 
        $this->fields = new fields($fields);
        //
        //the parent constructor
        parent::__construct($t, $joins, $wheres, $alias);
    }
    
    //
    //Returns the standard string representing the sql statement of a select
    public function to_str() {
        //
        //Construct the sql (select) statement
        $stmt = 
           "select \n"
               //
               //Field selection 
                . "{$this->fields->to_str()} \n"
            . "from \n"
                //
                //For now the root is simply the name of a table or a bracketed 
                //enclose sql         
                . "{$this->fromexp()} \n"
                //
                //The joins, if any
                . "{$this->joins->to_str()} \n"
                //
                //The where clause, if necessary    
              ."{$this->where->to_str()}";
        return  $stmt;        
    } 
   
}

//
//models the sql of type update where it requires the root entity, primary and the 
//array of key values pairs to be overwriten at the database 
//NOTE THE PRIMARY IS A VALUE NOT A ROOT COLUMN 
class update extends sql{
    //
    //The primary column required to formulate the criteria  
    public $column_primary;
    //
    //The entity that is the root of the update and it is from the root namespace
    public $entity;
    //
    //The value of the primary column of the row to be updated expected to be a number 
    //that is according to the standards we have for databases 
    public $primary_value;
    //
    //The value pairs of the column name for update 
    public $values;
    
    
    //
    //the constructor accepts the the root entity as entity, the primary value which 
    //is a literal and an array of key value pairs as values 
    function __construct(\entity $e, array $values, $primary){
        //
        //save the root entity
        $this->entity= $e;
        //
        //Get the primary column 
        $this->column_primary= $this->get_primary();
        //
        //save the values as an array of key value pairs 
        $this->values= $values;
        //
        //This is the value from which we create a where 
        $this->primary_value=$primary;
        //
        //get the parent joins and root components
        $root= new table($e);
        $joins= $this->joins();
        //
        //Create the parent
        parent::__construct($root, $joins, $wheres );
    }

    //
    //Retrieves the column name of this entity 
    function get_primary(){
        //
        //filter all the columns of this entity and remain with the primary columnn
        return array_filter($this->entity->columns, function($col){
            //
            //return only the primary 
            return $col->type==='primary';
        });
        
    }
    
    //
    //Get the joins of the update which are joins of the identifier
    function joins(){
        //
        //Create an identifier 
        $identify= new identifier($this->entity);
        //
        //Get the joins of the identify 
        $joins = $identify->joins;
        //
        //Return an array of the joins
        return $joins->get_array();
    }
}

//
//This is the various data formats that can be represented in an sql string 
//e.g literal, primary and a column
abstract class expression{
    //
    //The friendly name for this expression 
    public $alias;
    //
    function __construct($alias=null){
        //
        //Set the alias
        $this->alias=$alias;
    }
    //
    //An expresiion to string is implemented in various ways depending on what 
    //expression it is that is why it is an abstract method
    abstract function to_str();
    
}

//
//This is the simplest form of an expression it includes simple characters 
//e.g / , .
class literal extends expression{
    //
    //This is the value to be represented as an expression 
    public  $value;
    //
    //We require the value inwhich to express as an expression 
    function __construct($value){
        $this->value = $value;
        //
        parent::__construct();
    }
    //
    //Overiding the parent tostring inorder to represent a literal 
    function to_str(){
        //
        //A string version of a literal is basicaly the literal itself as a string 
        //hence it should be encosed in double quotes incase it is needed to be 
        //converted into json
        return "'$this->value'";
    }
}

//Note this primary could reference an sql it does not have to be an entity 
//
//
//
//This expression represent the sql primary column where its  construction requires 
//the root entity 
class primary extends expression{
    //
    //This is the entity that is the home of the column to be represented as a 
    //primary 
    public $entity;
    //
    //To create this expression we need the root entity 
    function __construct(\entity $e, $alias=null){
        //
        //Get the root entity 
        $this->entity= $e;
        //
        //The parent construction of this this requires an allis but since this 
        //for now there is no allias to this 
        parent::__construct($alias);
    }
    //
    //Override the string of this column by returnining the full version of the 
    //column name e.g for name = `client`.`name`  
    function to_str(){
        
         //Include the alias
        $alias= is_null($this->alias) ? "":"as `$this->alias`";
        return "`{$this->entity->name}`.`{$this->entity->name}`$alias ";
    }
    //
    //Displays the query results of the 
 }
 
 //
 //This represent a simple field in sql or an attribute in the root for its construction 
 //we require the root column
 class column extends expression{
     //
     //This is the root column that is to represented as an expression
     public $column;
     //
     //The construction includes a column and a volumntary allias that can be null
    function __construct(\column $col, $alias=null){
        //
        //Set the column and the alias  
        $this->column= $col;
         //
        parent:: __construct($alias );
        
    }
    
    //
    //Displays the query result of this expression
    function show($value){
        return "<td>"
                    . "$value"
              . "</td>";
    }


    //
    //stringfy the column to a valid sql string for this column in the full description 
    //of a field e.g `client`.`name` instead of name 
    function to_str(){
        //
        //Get the entity name using the magic function get parent since the 
        //parent entity is protected 
        $e = $this->column->get_parent();
        //
        //Include the alias
        $alias= is_null($this->alias) ? "":"as `$this->alias`";
        //
        //compile the complete string version of the  
        return "`{$e->name}`.`{$this->column->name}` $alias";
    }
 }
 
 //
 // A foreign expression is modeled as a foreign key and hence it requires a root 
 //column and a referenced sql name just incase it is not a fk
 class foreign extends expression{
     //
     //This is the referenced sql that this foreign linls to 
     public $ref_sql;
     //
     //this is the column that is used for referencing
     public $column;
     //
     //its constuction needs a root column and an optional ref_sql this is because if 
     //if the column passed is a fk the ref_sql is the ref table name 
    function __construct(\column $c, sql $ref_sql=null, $alias = null) {
        //
        //save the column 
        $this->column= $c;
         //
        //Set the referenced sql
        $this->ref_sql= is_null($ref_sql) ? $c->ref_table_name:$ref_sql->name;
        //
        //Set the alias 
        $this->alias=$alias;
        //
         parent::__construct($alias);
     }
     //
     //overide the parent to str
     public function to_str() {
         //
         //Get the parent of this column using
         $e= $this->column->get_parent();
         //
        //Include the alias
        $alias= is_null($this->alias) ? "":"as `$this->alias`";
         return "`$e->name`.`{$this->column->name}`  $alias";
     }
     //
    //Displays the query result of this expression
    function show($value){
        return   "<td onclick='review.select_td(this,$value)'ref='{$this->ref_sql}' title='$value'>"
                        . "$value"
                . "</td>";
        
    }
 }
 
 
//This models the sql function which require 
 //1. name e.g concat
 //2. array of arguments 
class function_ extends expression{
    //
    //These are the array of expressions
    public $expressions;
    //
    //This is the name of the function e.g concat 
    public $name;
    //
    function __consruct($name, array $expressions, $alias=null){
        //
        //Save the function name 
        $this->name = $name;
        //
        //Save the array of expressions 
        $this->expressions = $expressions;
        parent::__construct($alias);
    }
    //
    //Overide the parent to string 
    function to_str(){
        $expressions = $this->expressions;
        //
        //loop through the expressions returns a string for each 
        $strs = array_map(function($exp){
            return $exp->to_str();}
            //
            //Array that contains the expressions
            ,$expressions);
        //
        $args = implode(',', $strs);
        $alias = is_null($this->alias) ? "": " as `$this->alias`";
        //
        return "$this->name($args)$alias";
    }
    
    //
    //Displays the query result of this expression
    function show($value){
        return "<td>"
                    . "$value"
              . "</td>";
    }
   
}

//
//This models the concat expression that will be overridden by the class function
 class concat extends expression{
     
     function __construct(fields $f, $name=null){
         $this->fields= $f;
         
         parent::__construct($name);
     }
     
     function to_str(){
         //Include the alias
        $alias= is_null($this->alias) ? "":"as `$this->alias`";
        //
         return "concat ({$this->fields->to_str()}) $alias";
     }
     //
     //Displays the query result
     function show($value){
        return "<td>"
                   . "$value"
               . "</td>";
    }
 }

//
//This class class requires a preliminary knowledge of the following 
//1. The type of the join which is a string eg "OUTER JOIN", "iNNER JOIN" .....
//2. An array  of foreigns that are required to formulate the on clauses 
//3. The name of the sql or entity to be joined it should be as a complete string
//version such as select * from root inner join given_ename
class join{
    //
    //To create a join we need three things 
    //1.) the type of join as $type
    //2.) an array of the forigners as $foreigns 
    //3.) the Entity to joined as $given_ename
     function __construct($type,$foreigns, \entity $e){
         //
         //Every join must have a type which is a string i.e inner join 
         //outer join etc
         $this->type=$type;
         //
         //Get the join 
         $this->entity= $e;
         //
         $this->foreigns=$foreigns;
         //
         //Formulate the on clauses 
         $this->ons= $this->get_ons();
     }
     
     //
     //Maps the foreign array and a string return on statements required to 
     //formulate the join 
     //NOTE: this is for default if the foreigns are an array of columns if not 
     //column consider overriding this method 
     function get_ons(){
         //
         //Map for each and return an on clause 
        $col_str=array_map(array($this, 'map'), $this->foreigns);         //
         //
         //Return on joined on 
         return implode("AND \t",$col_str);
     }
     //The call back function
    function map($column){
        //
        //Get the entity name
        $entity=$column->get_parent();
        $ename=$entity->name;
        //
        //Get the cname
        $cname=$column->name;
        //
        //Get the referenced table name 
        $ref=$column->ref_table_name;
        //
        //Return a string version of the on clause
        return"\t{$ename}.{$cname}={$ref}.{$ref}\t";
    }
     //
     //strignfy to create a valid inner join that can be directly aappended to the
     //sql string  
     function to_str(){
         //
         //Get the name of the entity 
         $ename= $this->entity->name;
         //
         //The  type of the join eg inner join, outer join
         $join_str="$this->type"
         //
         //The on clause
         . "\t `$ename` \tON \t{$this->get_ons()}";
         return $join_str;
     }
    
}

//
//This class defines the criteria that is required to retrieve the data/information 
//The preliminary data required for its construction for now is a pair of the fields
//or the comparison appatatus required to formulate its string 
class where {
    //
    //Notice it accepts two fields in its constructor field1 and field2 there 
    //are the comparison parameters
    function __construct($field1, $field2) {
        //
        //Set the two fields as the properties of the class 
        $this->field1= $field1;
        $this->field2=$field2;
    }
    //
    //This method stringfys the where to create a valid where clause usable in sql 
    function to_str(){
        //
        //Get the string description of the two fields
        $field1_str= $this->field1->to_str();
        $field2_str= $this->field2->to_str();
        //
        //return a valid where clause
        return "$field1_str=$field2_str"; 
    }
}
//

//
//stores a collection of fields 
class fields extends \ArrayObject{
    
    function __construct(array $expressions) {
        
        parent::__construct($expressions);
    }
    
    function to_str($sep=null){
        //
        //Get a copy of this array, so that we can use the standard array methods
        $fields = $this->getArrayCopy();
        //
        //Map each field with its string version 
        $field_str=array_map(array($this, 'map_field'), $fields);
        //
        //
        $this->sep= is_null($sep) ? ",":$sep;
        //
        //Join the fields strings using a coma separator
        $field_sql=implode($this->sep, $field_str);
        return $field_sql;
    }
    //
    //Returns the array of this field
    function get_array(){
        return $this->getArrayCopy();
    }
    //
    //The callback function returns the string description of the fields 
    function map_field($field){
        $str= $field->to_str();
       //
       //return field to str
       return $str;
    }
}

//
//
//stores a collection of joins 
class joins extends \ArrayObject{
    
    function __construct($joins) {
        
        parent::__construct($joins);
    }
    
    function to_str(){
        //
        //Get a copy of this array, so that we can use the standard array methods
        $joins = $this->getArrayCopy();
        //
        //Test if this array is empty else 
        //If empty the sql since does not require joins
        if(empty($joins)){
            return "";
        }
        //
        //Map each field with its string version 
        $joins_str=array_map(array($this, 'map_joins'), $joins);
        //
        //Join the fields strings using a coma separator
        return implode("\n", $joins_str);
    }
    //
    //The callback function returns the string description of the fields 
    function map_joins($join){
       //
       //return field to str
       return $join->to_str();
    }
}

//
//stores a collection of wheres 
class wheres extends \ArrayObject{
    
    function __construct($wheres) {
        
        parent::__construct($wheres);
    }
    
    function to_str(){
        //
        //Get a copy of this array, so that we can use the standard array methods
        $wheres = $this->getArrayCopy();
        //
        //Test if the array is empty
        //
        //If empty return an empty string 
        if(empty($wheres)){
            //
            //
            return "";
        }
        //Make array unique to remove any dublicates
        $unique_wheres=array_unique($wheres);
        
        //Map each where with its string version 
        $wheres_str=array_map(array($this, 'map_wheres'), $unique_wheres);
        //
        //Join the wheres strings using a AND separator
        return implode("AND" , $wheres_str);
    }
    
    //
    //The callback function returns the string description of the fields 
    function map_wheres($where){
       //
       //return field to str
       return $where->to_str();
    }
}
