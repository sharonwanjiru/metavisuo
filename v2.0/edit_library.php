<?php
namespace sql;
//
//The edit aql class extends an entity which is defined at the library hence we 
//require a library 
require 'library.php';
require 'library_sql.php';
//
//The class edit eql extends the entity because through the entity we have access 
//to the columns and the indices which we require to generate the the indexed of the 
//various related entities 
//however we need to overwrite the get_joins and the fields method 
class identifier extends select{
    public $join_entities=[];
    //
    //We need the name of this entity as the root of this sql 
    //..........Problem faced class entity is not extendable because of the 
    //comment variable 
    function __construct(table $t) {
        //
        //save the entity
        $this->entity=$t->entity;
        //
        //Get the fields of this select sql
        $fields = $this->get_fields();
        //
        //Get the joins of this sql
         $joins= $this->joins();
        parent::__construct($t, $fields, $joins, []);
    }
    
    //
    //Return the attributes of an entity that are used for identification as the fields 
    //of this sql overriding the fields of an entity
    function get_fields(){
        $foreigns=[];
        //        
        //Get the indexed column names
        $index_names = $this->entity->indices;
        //
        //Get the first index
        $index= array_values($index_names)[0];
        //
        //loop through the array and only yield the attributes 
        foreach ($index as $cname){
            //
            //Get the column with rhat name 
            $col=$this->entity->columns[$cname];
            //
            //Test if the column is an attribute 
            if($col instanceof \column_attribute){
                //
                array_push($foreigns, $col);
            }
            else{
                //
                //Get the referenced table name 
                $ref= $col->ref_table_name;
                //
                //Test if this entity exists in the join entities list
                //1 if it does not exist push 
                $en= array_search($ref, $this->join_entities);
                if ($en == false){
                    //
                    array_push($this->join_entities, $ref);
                }
                //
                //Get the referenced entity 
                $entity1= $this->entity->dbase->entities[$ref];
                //
                //Create an sql edit from it
                $table1= new table($entity1);
                $sql_edit1= new identifier($table1);
                //
                //Get the fields from it 
                $cols2= $sql_edit1->get_fields();
                $enames= $sql_edit1->join_entities;
                // 
                foreach ( $cols2 as $coll) {
                    //
                    //push the column attribute
                   array_push($foreigns, $coll);
                }
                //
                //Include the joins of the referenced entity
                foreach ( $enames as $ename) {
                    $enn= array_search($ename, $this->join_entities);
                    //
                    if ($enn == false){
                      //
                       array_push($this->join_entities, $ename);
                    } 
                }
            }
            
        }
        //
        //Update and include any name, title, description , or id fields
        $fields=$this->update_fields($foreigns);
        //
        //
        return $fields;
    }
    
    //Update the fields to include any nane, title, description or ids that are not 
    //indexed 
    function  update_fields($fields){
        //
        //loop through all the columns in this entity 
        foreach ($this->entity->columns as $column) {
            //
            //Get the name of the column
            $name= $column->name;
            //
            //Test if the column already exist in the fields array
            $names= array_map(function ($f){return $f->name;}, $fields);
            //
            $enn= array_search($name, $names);
            //
            //If the culumn is an indexed column do nothing if false do the following
            if ($enn === false){
                //
                //Test if it is called a name 
                if ($name== 'name'){
                    //
                    array_push($fields, $column);
                }
                //
                //Test if it is called a description 
                if ($name== 'description'){
                    //
                    array_push($fields, $column);
                }
                //
                 //Test if it is called a title 
                if ($name== 'title'){
                    //
                    array_push($fields, $column);
                }
                //
                 //Test if it is called a id 
                if ($name== 'id'){
                    //
                    array_push($fields, $column);
                }
            }
        }
        //
        //
        return $fields;
    }

    //
    //Overiding the joins of the entity to create the joins of this edit sql 
    function joins() {
        //
        //begin with an empty array of joins 
        $joins=[];
        //
        //Clean the array to remove any dublicates
        //
        //Sort the array in order of dependency
        $unsorted_entities=[];
        foreach ($this->join_entities as $enames){
            //
            //Get the entities
            $entty= $this->entity->dbase->entities[$enames];
            array_push($unsorted_entities, $entty);
        }
        
        //loop through the sorted array creating a join from each 
        foreach ($unsorted_entities as $entity){
          //
          //get the ands columns
          $foreigns= $this->get_foreigners($entity, $joins);
          //
          //Create a new join 
          array_push($joins, new join('INNER JOIN', $foreigns, $entity));
        }
        //
        //Return the collection of the joins in an array 
        return $joins;
    } 
    //
    //Returns the foreiners in an array
    function get_foreigners($entity, $joins){
        //
        //let $foreigns be the array of foreigners to be returned 
        $foreigns=[];
        //
        //Get the already existing join entities and store them in an array 
        //$join_entities
        $join_entities=[];
        //
        //Test if there are joins already formulated if none push this entity 
        if(empty($joins)){
           array_push($join_entities, $this->entity);
        }
        //
        //There are joins already existing 
        foreach ($joins as $join){
            //
            //Push all the entities to the join entities
            array_push($join_entities, $this->entity);
            array_push($join_entities, $join->entity);
        }
        //
        //Get the columns that reference the given entity
        foreach ($join_entities as $entity1){
            //
            //Get the first index
            $index1= array_values($entity1->indices)[0];
            //
            //loop through indices to retrieve the column foreigns
            foreach ($index1 as $cname){
                $column= $entity1->columns[$cname];
                //
                //Test if is an instance of column foreign
                if($column instanceof \column_foreign){
                    //
                    //Get the referenced entity 
                    $entity2=$this->entity->dbase->entities[$column->ref_table_name];
                    //
                    //Test if entity2 is similar to the given entity
                    if ($entity2===$entity){
                        //
                        //push the column to the foreigns
                        array_push($foreigns, $column);
                    }
                }
            }          
            //
            //Get the entities being refereced by the given entity
            $index= array_values($entity->indices)[0];
            //
            //loop through indices to retrieve the column foreigns
            foreach ($index as $cname){
                $column= $entity->columns[$cname];
                //
                //Test if is an instance of column foreign
                if($column instanceof column_foreign){
                    //
                    //Get the referenced entity 
                    $entity2=$this->dbase->entities[$column->ref_table_name];
                    //
                    //Test if entity2 is similar any entity referenced
                    if ($entity2===$entity1){
                        //
                        //push the column to the foreigns
                        array_push($foreigns, $column);
                    }
                 }
            }
        }
        return $foreigns;
    }
}

//The class has a special field called concat 
class selector extends identifier{
    //
    function __construct(table $t) {
        //
        //Initialize the identifier sql
        parent::__construct($t);
        //
        $c= $this->entity->columns[$t->entity->name];
        //
        //Create the foreign field of this selector
        $foreign = new foreign($c, $this, $this->name);
        //
        //This literal acts like a separator of this fields to make them the 
        //arguments of the sql function 
        $sep = new literal('-');
        //
        //let the args be field of the parent identifier awaiting separation by 
        //the $sep 
        $args = $this->fields->getArrayCopy();
        //
        //let args2 be the  collection of the the parent fields together with 
        //their respective separator 
        $args2=[];
        //
        //Loop through the args pushing in a separator between them
        foreach($args as $arg){
            array_push($args2, $arg);
            array_push($args2, $sep );
        }
        //
        //Remove the last separator from the list 
        array_pop($args2);
        //
        //Create the concated fields using the function name concat
        $concated = new concat(new fields($args2), "{$this->entity->name}_ids");
        //
        //Create the fields array
        $fields=[];
        array_push($fields, $foreign);
        array_push($fields, $concated);
        //
        $this->fields = new fields($fields);
    }
}

//
//Outputs all the colummns with foreign keys resolved to their friendly
//identifiers. E.g., client=4 is resolved to client=[4,"deekos-Dessoks Bakery Ltd."]
class editor extends identifier{ 
    //
    function __construct(table $t){
        parent::__construct($t);
        //
        //Collect all the  fields of the editor
        $fields = iterator_to_array($this->yield_fields(), true);
        //
        //Override the feilds property
        $this->fields = new fields($fields);
    }
    
    //Yield every field of this editor
    function yield_fields(){
        //
        //Visit each column of the root entity, resolve it.
        foreach($this->entity->columns as $column){
            //
            //Resolve the current column
            //
            //Primarye keys and attributes to not need resolving
            if($column instanceof \column_primary){
               yield new primary($this->entity, $column->name); 
            }
            
            else if ($column instanceof \column_attribute){
                yield new column($column, $column->name);
            }
            //
            //A forein key needs resolving from e.g., client=4 to
            //client = [4,"deekos-Deeoks Bakery lt"]. We need to cocaat 5 pieces
            //of data, $ob, $primary, $comma, $dq, $friendly, $dq, $cb
            else{
                //Start with an empty array 
                $args=[];
                //
                //Opening bracket
                $ob= new literal('[');
                array_push($args, $ob);
                //
                //Primary 
                $primary = new column($column);
                array_push($args, $primary);
                //
                //Comma
                $comma= new literal(',');
                array_push($args, $comma);
                //
                //Double quote
                $dq= new literal('"');
                array_push($args, $dq);
                //
                //Yied the fiedly comumn name
                $e2 = $this->entity->dbase->entities[$column->ref_table_name];
                //
                //Create an sql table from the entity 
                $t= new table($e2);
                //
                //To obtain the indexed attributes get the identifier
                $identify = new identifier($t);
                //
                //The friendly are the fields of the identifier
                $friendly=  $identify->fields;
                //
                //Return an array of the fields 
                $fri_fields= $friendly->get_array();
               
                //Loop through the array and pushing every component 
                foreach ($fri_fields as $field){
                    array_push($args, $field);
                    $d= new literal("/-/");
                    array_push($args, $d);
                }
                array_pop($args);
                //
                //Double quote
                 array_push($args, $dq);
                 //
                $cb= new literal(']');
                array_push($args, $cb);
                //
                yield new concat(new fields($args),$column->name);
               
            }
        }
    }
    
    //
    //Present the editor sql to support editing of tabular data
     function show($dbase){
        $id= $this->name;
        //
        //Execute this sql 
        $array= $this->query($dbase);
        //
        //Ouptut a table
        echo "<table id='fields'>";
        echo $this->header();
         //
        //Loop through the array and display each row as a tr  element
        foreach ($array as $row) {
            //
            echo "<tr onclick='review.select(this)' id='$id'>";
            //
            //loop through the column and out puts its td element.
            foreach ($this->entity->columns as $col){
                //
                //Get the value from the row, remebering that the row is indexed
                //by column name
                $value = $row[$col->name];
                //
                //
                echo $col->show($value);
            }
            //
            echo "</tr>";
        }
        
        echo "</table>";
        
        
    }
}