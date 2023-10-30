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
            //Create a new javascript database 
            const dbase = new database(<?php echo $static_dbase; ?>);
            //
            //Get an entity from the dbase for testing 
            const entity= dbase.entities['client'];
            //
            //Get the indexed columns 
            const indices = Object.values(entity.indices)[0];
            //
            //test if the indexed columns contain a foreign key if true trace back 
            //the entity to a point where the indiexed columns are not foreign else 
            //formulate the sql of that entity 
            //Get the foreign key column names used for identification.
            const f_col = Object.values(entity.columns).filter(column =>{
                //
                //Test if the column is foreign and is part of the identification index
                const x = column.constructor===column_foreign;
                const y = indices.includes(column.name);
                return  x && y;
                }
           );
           //
           //The entity does not contain a foreign key in any of its indices hence 
           //formulate its sql 
           if(f_col.length === 0){
             //
             //The sql contains the indexed columns and any other relevant information 
             //for its identification 
             //
             //for now we will select everything 
             const sql = `SELECT * FROM ${entity.name}`;
           } 
           //
           //Else track back the entity and formulate referenced to identify if 
           //it contains a foreign key 
           else{
               //
               //Create an array that will store all the referenced tables 
               const ref_tables = [];
               //Map cname's entity with its indexed columns  
                const indexes = f_col.map(column=>{
                    //
                    //Get the referenced entity name
                    const ename = column.ref_table_name;
                    //
                    //Get the actual entity
                    const ref_entity = dbase.get_entity(ename);
                    //
                    //Get the indices of the referrenced entity 
                    const ref_indices = Object.values(ref_entity.indices)[0];
                    //
                    //Get the collection of indices that are foreign
                    get_indices(column);
                    //
                    //if there are no foreign keys return the indexed columns 
                    if (ref_col.length===0){
                        //
                        //Push the referenced table 
                        ref_tables.push(ref_entity.name);
                        //
                        //return the indexed cnames 
                        return ref_indices;
                    }
                    //
                    //else repeat the same process
                    else{
                        //
                        //loop through the column and get the indices of the referenced 
                        //table 
                        ref_col.forEach(column=>{
                          //
                          //
                          return get_indices(column);
                        });
                    }

                });
                //
                //for deburging see the structure 
                 console.log(indexes);
                //
                //Create the sql using the various indexed columns of the mapped
                //array 
                const sql = `SELECT `
                
            }
            
            //
            //
           //Returns the indices of an entity  
            function get_indices(cname, ref_tables){
                //
                //Get the referenced entity name
                const ename = cname.ref_table_name;
                //
                //Get the actual entity
                const ref_entity = dbase.get_entity(ename);
                //
                //Get the indices of the referrenced entity 
                const ref_indices = Object.values(ref_entity.indices)[0];
                //
                //Test if the ref_indices contain a foreign keys 
                const ref_col = Object.values(ref_entity.columns).filter(column =>{
                    //
                    //Test if the column is foreign and is part of the identification index
                    const x = column.constructor===column_foreign;
                    const y = indices.includes(column.name);
                    return  x && y;
                });
                //
                //if there are no foreign keys return the indexed columns 
                if (ref_col.length===0){
                    ref_tables.push(ref_entity.name);
                    //
                    //
                    return ref_indices;
                }
                //
                //else repeat the same process
                else{
                    //
                    //loop through the column and get the indices of the referenced 
                    //table 
                    ref_col.forEach(column=>{
                      //
                      //
                      return get_indices(column);
                    });
                }
            } 
            //
            
        </script>
    </head>
    <body>
       
    </body>
</html> 