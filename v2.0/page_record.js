//
//Set as global variables because they are passe froom one window to another 
var current_dbase=null, entity_= null ;

class record{    
    //
    //Retrieves and sets the current database that is saved at the window object 
    static get dbase(){
        //
        //Test if the the current database 
        if(current_dbase===null || current_dbase===undefined){
            //
            //Get the dbase saved at the window object 
            current_dbase=window.__dbase__;
            //
            //Return the dbase 
            return current_dbase;
        }
        //
        //Return the already set dbase
        else{
            
            return current_dbase;
        }
    }
    
    //
    //Get the current entity of operation whose record is being edited
    static get entity(){
        //
        //test if the entity is already set
        if(entity_===null || entity_===undefined) {
            //
            //get the current database 
            const dbase = this.dbase;
            //
            //Get the table dom element
            const table= document.querySelector("table");;
            //
            //Get the name of the entity that is involved
            const name=table.getAttribute('name');
            //
            //Get the alterable entity from the alterable database
            entity_= dbase.entities[name];
            //
            //return the entity
            return entity_;
        }
        //
        //Return the set entity
        else {
            return entity_;
        }
    }
    
    //
    //Selects a row on the table for further processing 
    static select(tr){
        //
        //remove any previous selection 
        const prev = document.querySelector(".clicked");
        if(prev !== null){
          prev.classList.remove('clicked');
        }
        //
        //Add a classlist of selected to the tr
        tr.classList.add('clicked');
    }
    
    //
    //Updade the raw 
    static update(){
        //
        //Get the selected td 
        const td = document.querySelector(".selected");
        //
        //Test if there is a selected row if no selected row alert the user to 
        //select a row
        if(td === null){
          alert('Please select the updated row');
        }
        //
        //Get the type of the column to be updated 
        const type= td.getAttribute('type');
        //
        //If the name is an attribute make the td editable and save the updated 
        //text content 
        if(type==='attribute'){
            this.edit_attribute(td);
        }
        //
        //if the type is a primary do nothing 
       if(type==='primary'){
            //
        }
        //
        //If the type is a foreign the column open the new window
        if(type==='foreign'){
            this.edit(td.title, td.getAttribute('name'));
        }
        else{}
    }
    
    //
    //Enables the editing of an attribute and saves the updated value 
    static edit_attribute(td){
        //
        //Get the text content 
        const content = td.textContent;
        //
        //Change its inner html to editable 
        td.innerHTML=`<td><div contenteditable tabindex="0">${content}</div></td>`;
    }
    
    //
    //Opens a new window (selector page) of the referenced entity ref using the 
    //selector sql
    static edit(title=null,ref){
        //
        //Get the current dbase name
        const dbase= this.dbase;
        const dbname= dbase.name;
        //Set the selector page to the new opened window
        const page_selector = window.open(`page_selector.php?ename=${ref}&dbname=${dbname}`);
        //
        //Set this page as a property of this record 
        this.page_selector=page_selector;
        //
        //Set the database as a property of the selector
        this.page_selector.__dbase__=this.dbase;
        //
        //Ensure that the previously selected is selected at the selector
        this.page_selector.addEventListener("onload", this.selector_update(title));
        //
        //Retrieve the selected data using the before unload event listener
        //
        //Retrieve the primary value, friendly name and the referenced table name
        const {primary, friendly, ref_table_name}=this.page_selector.
                //
                //The data is retrieved using the before unload event listener
                addEventListener('onbeforeunload', this.get_selector_data());
        //
        //Get the edited td from the editor 
        const td = window.document.querySelector(`${primary}_${ref_table_name}`);
        //
        //Change its content to the new friendly that was selected 
        td.innerHTML=`<td ref='${ref_table_name}' id='${primary}_${ref_table_name}'
                        type='foreign' title='${primary},${friendly}'>
                        ${friendly} 
                    </td>`;
    }
    
    //
    //Get the data to be saved as a key value pair of the following structure
    //{ename,primary [{cname:value}...]}
    static data($status){
        //the data to be returned
        var data, ename, primary;
        //
        //Get the selected tr inwhich the salected data is found  
        const tr = document.querySelector('.clicked');
        //
        //1.THE ENAME
        //Get the entity's name as ename saved as the id of the tr
        ename= tr.getAttribute('id');
        //
        //2. THE PRIMARY
        //
        //Get the tds that have the data to be saved  
        const tds= tr.childNodes; 
        //
        //Loop through the tds to extract the information saved by the type of the 
        //column
        //start with an empty array to store the various attibutes
        const attributes=[];
        for(let i=0; i<tds.length; i++){
            //
            //Get the ith td
            const td=tds[i];
            //
            //Get the type of the column
            const type= td.getAttribute('type');
            //
            //Test for the primary
            if(type==='primary'){
                const pri= td.textContent;
                primary= eval(pri);
            }
            //
            //3 GET THE OTHER COMPONENT COLUMNS
            //Get the attributes
            if(type==='attribute'){
                //get the cname
                const name=td.getAttribute('name');
                //get the updated value
                const value= td.textContent;                
                //
                attributes.push({name, value});
            }
            //Get the foreigns
           if(type==='foreign'){
               //get the cname
                const name=td.getAttribute('name');
                //
                //Get the updated value
                const title=td.getAttribute('title'); 
                const value1= eval(title);
                const value= value1[0];
                //
                attributes.push({name, value});
           }
           data={ename,primary,attributes};
        }
        const values=JSON.stringify(attributes);
        if($status){
            //
            //save update in php first test it using 
            //pass the parameters in the query string
            const win = window.open(`update.php?ename=${ename}&dbname=mullco_rental&primary=${primary}&attributes=${values}`);
        }
        else{
            const win = window.open(`delete.php?ename=${ename}&dbname=mullco_rental&primary=${primary}&attributes=${values}`);
        }
    }
    
    //update the new window marking as selected the given field
    static selector_update(pri){
        const primary= eval(pri);
        //Get the selected raw and mark it as selected 
        const tr = this.page_selector.document.getElementById(`${primary[0]}`);
        //
        //Add a classlist of selected to the tr
        tr.classList.add('clicked'); 
    }
    //
    //retrieves any data that was passed by the widow as selected
    static get_selector_data(){
        //
        //get the primary 
        return {primary, friendly, ref_table_name} = page_selector.selected_;
    }
    
    //
    //creates a record from the editor by inserting a blank row showing all the 
    //a) attributes as editable fiekds and b) foreign columns as buttons. 
    static create(){
       //
       //Get the alterable entity 
       const entity= this.entity;
       //
       //Get the tr created by the alterable entity 
       const tr = entity.create_td();
       //
       //Insert the tr after the first child since the first child is a header 
       //and the newly created should be after the header
       const table_body = document.querySelector("table").querySelector("tbody");
       table_body.insertBefore(tr,table_body.childNodes[0]); 
    }
    
    //
    //Marks a td as selected 
    static select_td(td){
        //remove any previous selection 
        const prev = document.querySelector(".selected");
        if(prev !== null){
          prev.classList.remove('selected');
        }
        //
        //Add a classlist of selected to the tr
        td.classList.add('selected');
        //
        //focus on the selected td 
        td.autofocus;
        td.focus();
    }
    
    //
    //Create a new record for the selected entity using a new window
    static create_selector_record() {
        //
        //Get the entity of operation 
        const entity= this.entity;
        //
        //Open an empty brand new window that displays the label format
        let $win = window.open("page_create.php");
        //
        //save the database at the window object 
        $win.__dbase__=this.dbase;
        //
        //Onloading the window loop through all the columns in this entity 
        //displaying their inputs 
        $win.onload = () => {
            //
            //Get the $body element of $win (window).
            let body = $win.document.querySelector('form');
            //
            //looping through all the columns to create a label inputs
            for (let cname in entity.columns) {
                //
                //Get the named column
                let column = entity.columns[cname];
                //
                //Append all the column as lables appended to the body of the new window 
                column.inputs(body);
            };
        };
        //
        //Set the database as a property of the creator
        $win.__dbase__=this.dbase;
        //
        //set it as a property of the class so as to access using other methords
        this.creator= $win;
        
        

    }
    
    //
    //Collects all the data in the label layout of the select sql in the following 
    //format{ename, [{attribute,value}......]}
    static label_data(){
        //
        //Start by getting the affected entity
        const entity= this.entity;
        //Retieve the name of the entity 
        const ename= entity.name;
        //
        //Get the form from creator window 
        const form = this.creator.querySelector('form');
        //
        //Get the inputs for every column from  the form 
        const inputs= Array.of(form.querySelectorAll('input'));
        //
        //Begin with an empty array of attributes
        const attributes=[];
        //
        //loop through all the inputs creating a name and a value pair abject
        inputs.forEach(input=>{
           //
           //Get the name of the column with the input
           const name= input.textContent;
           const value= entity.columns[name].get_value();
           //
           //Push the pair in the attributes array
           attributes.push({name,value});
        });
        //
        const values=JSON.stringify(attributes);
        //
        //save update in php first test it using 
        //pass the parameters in the query string
        const win = window.open(`update.php?ename=${ename}&dbname=mullco_rental&attributes=${attributes}`);      
    }
}