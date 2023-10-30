<?php
    //Include the library 
    include 'library.php';
    //
    //Create a new database object 
    $dbase = new database($name='mullco_rental', $username= 'mutallco', $password ='mutall_2015');
    //
    //Get the entities of this dbase 
    $dbase->export_structure();
    //
    // encode the static dase
    $static_dbase= json_encode($dbase);
    
?>


<html>
    <head>
        <title> Graph</title>
        <link rel="stylesheet" type="text/css" href="graph.css">
        <script src='library.js'></script>
        <script>
            
             //
             //Class query 
             class query{
                 constructor(root_entity){
                     //
                     //Set the root Entity
                     this.root_entity= root_entity;
                     //
                     //Develop the id sql for this root entity passed the root entity 
                     //as an argument because this method can be used to generate 
                     //id sqls using other related entities.
                     this.id_sql=this.get_id_sql(this.root_entity);
                     //
                     //formulate the joins for this root entity
                     this.joins= this.get_joins(this.root_entity);
                 }
                //
                //Gets the sql that a group of entities using a join 
                get_id_sql(entity){
                    //
                    //these are the consistent id attributes for this root entity 
                    this.id_attributes = [...get_id_attributes(entity)];
                    //
                    //Map the id_attributes to their constituent entitynames and columnname
                    //the format used jointly to formulate the string id sql;
                    fnames = this.id_attributes.map(field=>{
                        const ename = field.entity.name;
                        const fname = field.name;
                        return `${ename}.${fname}`;
                    }).join('-');

                    //
                    //
                    const joins = get_joins(entity1, attributes);
                    //
                    const join_str = joins.map(join=>{
                        return `inner join ${join.ename} on ${join.ands}`;
                    });
                    //
                    const sql = `select concat(${fnames}) as _id ${rest} from ${entity.name} ${joins_str}`;

                    return sql;
                }
                //
                //Returns an array of column_attributes that are used for identification 
                //of the given entity
                *get_id_attributes(entity1){
                    //        
                    //Get names of columns for first index of the indices of this entity 
                    const index1 = Object.values(entity1.indices)[0];
                    //
                    //Map the column names and create an array of the columns 
                    //referrenced by this column name 
                    const cols = index1.map(cname=>entity1.columns[cname]);
                    //
                    //For each column loop through to produce the constituent
                    //column attributes
                    for(const col of cols){
                        //
                        //Test if the column is an attribute 
                        //1. if true yield a column 
                        if (col instanceof column_attribute){
                            yield col;    
                        }
                        //
                        //2. If not an attribute get the referenced entity from 
                        //for which get the id_attributes 
                        else{
                            //
                            //Col is a foreign key. Get the id attributes of the 
                            //constituent reference entity 
                            //
                            //Get the referenced table name  
                            const refename = col.ref_table_name;
                            //
                            //Get the referenced entity 
                            const entity2 = dbase.entities[refename];
                            //
                            //Generate the id attributes of the referenced entity 
                            yield *get_id_attributes(entity2);
                        }
                    }
               }
               
                //
                //Get the joins of the root entity 
                //
            //Return an array of joins 
             get_joins(root_entity){
                //
                //Get the entities in the attributes that are required to formulate
                //the required join 
                const dirty_entities = this.id_attributes.map(attribute=>attribute.entity);
                //
                //Remove duplicate entities
                const unsorted_join_entities = this.clean(dirty_entities).filter(entity=>entity!==root_entity);
                //
                //Sort the unorderd joins by order of depenedency
                join_entities =  sort(unsorted_join_entities);
                //
                const joins=[];
                //
                for(const entity of join_entities){
                    //
                    //Sort them by dependency 
                    const column_foreigns = get_ands_foreigners(root_entity, joins, entity);
                    //
                    const $join = new join(entity, column_foreigns);
                    joins.push($join);
                   }
                //
                return joins; 
            }
            
            //
            //Cleans an array and returns one without dublicates
            clean(dirty_entities){
                //
                //Convert the dirty entities to a set since a set does not accept 
                //duplicates
                const entities_set= new Set([...dirty_entities]);
                // 
                //Return the result as an array not a set so as to exploit the 
                //the various array methods 
                return [...entities_set];
            }
        }
            
            //
            //Create a new javascript database 
            const dbase = new database(<?php echo $static_dbase; ?>);
            //
            //Get an entity from the dbase for testing 
            const entity= dbase.entities['client'];
            
            const sql = get_id_sql(entity);
            console.log(sql);
        </script>
    </head>
    <body>
       
    </body>
</html> 