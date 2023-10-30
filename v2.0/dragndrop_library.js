
class dragndrop{
    //
    //This class requires a line and an ellipse for its construction 
    constructor(){ 
        //Get the svg element where the drag and drop is bound. which 
        //creates the instance of the class on load 
        this.svg= document.querySelector('svg');
        //
        //Add this removes any other element that could have been selectes an retains 
        //only the target element as tthe selected element 
         this.svg.addEventListener('click', (e)=>{this.get_selected(e);}, false);
        //
        //the arrow function was used inoder this can refer to the class dragndrop not svg 
        //Add the event listeners from which the line is bound.
        //Occurs upon the mouse touch on an element
        this.svg.addEventListener('mousedown', (e)=>{this.start_drag(e);}, false);
        //
        //Occurs when the user moves the mouse over the element also 
        //called a drag.
        this.svg.addEventListener('mousemove', (e)=>{this.drag(e);}, false);
        //
        //occurs when the left mouse button is released over the selected
        // element also called a dragend.
        this.svg.addEventListener('mouseup', (e)=>{this.end_drag(e);}, false);
    }
    //Removes the property of selected from all the entities to all the ellipses in the dome
    get_selected(e){
        //
       //Get the target element 
       const target= e.target;
       //
       //Test if the selected element is selectable 
       const selectable = this.is_selectable(e);
       //
       //The target element is selectable so clean ip the previous selection and 
       //set a new selected element
       if(selectable===true){
            // Test if there was a selected lement before 
            // 
            //Get all the previously selected elements 
            if(document.querySelectorAll('[selected=true]')=== null || document.querySelectorAll('[selected=true]') === undefined){
                //
                //do nothing
                return;
            }
            else{
             const last_selections = document.querySelectorAll('[selected=true]');
             console.log('true');
             //
             //Clean up the currebt selection(s)
             for(let i=0; i<last_selections.length; i++){
                  //
                  const last_selection = last_selections[i];
                  //
                  //Remove the attribute of selected
                  last_selection.removeAttribute('selected');
              }
              //Set the selected element to be dragged to be the one clicked  
              //{the target element to which the drag will occurs}
              const selected_element= target;
              //
              //Set a new selected element
              selected_element.setAttribute('selected', true);  
         }
      }
    }
    //Returns the translated coodinates in svg version as X and Y coodinates 
    get_mouse_position(e) {
       //
       //
       const pt1 = this.svg.createSVGPoint();
       //
       pt1.x = e.clientX;
       pt1.y = e.clientY;
       //
       const pt2 = pt1.matrixTransform(this.svg.getScreenCTM().inverse());
       //
       //Return two properties the x and the y reprresenting the x and y coodinates.
       return {
           x:pt2.x,
           y:pt2.y 
       };
    }
    
    //Set the selected element to null to end the drag process
    end_drag() {
        //
        //Set the selected eleement to false
        this.selected_element = false;
     }
   //
   //Overwritting the start drag function so as to channge the drag effect from a 
   //draggable element to a group 
   start_drag(e){
       //
       //Ensure that we can only drag a group g tag. Anything else is ignored.
       if (!e.target.classList.contains('draggable')) return;
       
        //
        //The selected element that is suppost to be dragged 
        this.selected_element= e.target; 
         //
        //get the parent group tag <g> to be draged
        this.selected_group= this.selected_element.parentElement;
        //
        //Set the mouse position at teh start of the draf position in svg 
        //coordinatees
        this.coord_start = this.get_mouse_position(e);
        //
        //Including the transform property to the selected element 
        const transforms = this.selected_group.transform.baseVal;
        //
        // On start drag the length ie distance betweeen the click and the 
        // mouse position is 0 hence the translate matrix is 0 0. 
        if (transforms.length === 0 || transforms.getItem(0).type !== SVGTransform.SVG_TRANSFORM_TRANSLATE) {
            //
            // Create an transform that translates by (x,y) since it is two
            // dimentional
            const transform = this.svg.createSVGTransform();
            //
            //Set the translate parameters as 0,0 since no change in position at the drag start 
            transform.setTranslate(0, 0);
            //
            //Apend the translate parameter to the selected element. (0 is the
            //index position where you are inserting
            this.selected_group.transform.baseVal.insertItemBefore(transform,0);
         }
        this.transform = transforms.getItem(0);
        //
        //Get the translated mouse position both in the x and the y 
        //direction after setting the x,y translate parameters to 0,0
        this.coord_start.x -= this.transform.matrix.e;
        this.coord_start.y -= this.transform.matrix.f;
   }
   //
   //Gets the text in the group and makes it more interactive 
   remove_text_selected(){
       if (this.selected_group){
            //
            //Get all the tspan text element in the selected group
            let tspans= this.selected_group.childNodes;
            console.log(tspans);
            //
            //loop through all the tspans and remove the selected  atttribute
            for (let i=0; i<tspans.length; i++){
               //
               let tspan= tspans[i];
               tspan.removeAttribute('t_selected');
                //Add an event listener on each tspan to enable select
                tspan.addEventListener('click', this.make_selected(ev));
            }
      }
   }
   
   //
   //Returns a true ,or a false to the elements that are selectable 
   is_selectable(e){
       //
       //An element is selectable if it is either a line or an ellipse
       //
       //Test if the element is a line 
        if (e.target.nodeName==="line"){
            return true;
       }
       //
       //test if the element is an ellipse
       if (e.target.nodeName==="ellipse"){
            return true;
       }
       //
       //when neither rerturn a false
       else {return false;}
   }
   
    //Change the translate arguments from 0 to new position to allow 
    //a change in position. 
    drag(e) {
        //
        //The drag function can only take place if there is a selected element to
        //avoid dragging the entire svg element.
        if (this.selected_element) {
            //
            //set the new translated position as a variable coord
            //{the actual coodinates ofter the translation of the selected element}
            this.coord_end = this.get_mouse_position(e);
            //
            //Pass the arguments of the translate as the distance between
            // the mouse position and the translated position. 
            this.transform.setTranslate(this.coord_end.x - this.coord_start.x, this.coord_end.y - this.coord_start.y);
            //
            //Drag the entity companions as well
            this.drag_line_companions();
            //Update the cx and the cy of the ellipse;
            this.selected_element.setAttribute('cx', this.coord_start.x);
            this.selected_element.setAttribute('cy', this.coord_start.y);
            //
            //get the entity name of the selected element 
            let e_name= this.selected_element.getAttribute('id');
            //
            //Get the text 
            const text= document.getElementById(`_${e_name}`);
            this.drag_text_companions(text);
            //
            //set a new attribute to the selected element 
            this.selected_element.setAttribute('newx', this.coord_end.x);
            //
            this.selected_element.setAttribute('newy', this.coord_end.y);
        }
        
    }
    //
    //drag the text companions to avoid dislocating the text
    drag_text_companions(text){
        text.setAttribute('x', this.coord_start.x);
        text.setAttribute('y', this.coord_start.y);
        //
        //get the tspans 
        
    }
    
   //
   //drags the line and the circle that represents the start of a relation 
    drag_line_companions(){
        if (this.selected_element){
            //
            //Get the line which has the same coodinates as the ellipse 
            const lines= document.querySelectorAll('line');
             //
             //loop through all the lines to abtain the start and the end attributes 
             //then compare them with the id names of the ellipse 
             for(let i=0; i<lines.length; i++){
                 //
                 const line= lines[i];
                 //
                 //Get the line id which contains the name of the ellipse linked to it
                 let name = line.getAttribute('id');
                 let lname = name.split(".");
                 //
                 //get the entity name of the selected element 
                 let e_name= this.selected_element.getAttribute('id');
                 //
                 //When the first member of the array matches the ellipse set the 
                 //start of the line to the new translted coodinates 
                 if(e_name=== lname[0]){
                        //
                      const point=  this.entity_companion(line.getAttribute('x2'),line.getAttribute('y2'));
                      //
                      //If the intersecting point is not clearly defined console the error
                      if (point===null|| point===undefined){
                          //
                       }
                      else{
                        line.setAttribute('x1', point.x);
                        line.setAttribute('y1', point.y);
                        //
                       //drag the circles that show the start of the relation
                       const circle= document.getElementById(`${lname[0]}_${lname[1]}`);
                       this.circle_drag(circle, point);
                     }
                 }
                 //
                 //when resembles the second change the end of the line 
                 else if(e_name=== lname[1]){
                    const point=  this.entity_companion(line.getAttribute('x1'),line.getAttribute('y1'));
                    //
                    //incase the ellipses are overlapping or have similar coordinates 
                    //do not draw the relation 
                    if (point===null|| point===undefined){
                        //do nothing 
                     }
                    //
                    //Let the relations follow
                    else {
                        line.setAttribute('x2', point.x);
                        line.setAttribute('y2', point.y);
                    }
                 }
                 //
                 //if not equal do nothing 
                 else{
                     //    
//                     console.log('true');
                 }
            }       
        }
    }
    //
    //This is the entity component declared using the kevin lendsey library and 
    //return the intersection points
    entity_companion(x,y){
        //
        //convert the arguments into integers
         const end_x= parseInt(x);
         const  end_y= parseInt(y);   
        
         //
            //Define the coordinates of the drag ellipse which is the mouse position  
            const start = new KldIntersections.Point2D(this.coord_end.x, this.coord_end.y);
           //
           //{Pplot the selected entity ellipse that is undergoing the drag  
            const ellipse = KldIntersections.ShapeInfo.ellipse(start.x, start.y , 100, 50);
            //
            //Plot the line wich is to follow the ellipse intersection point 
            const line = KldIntersections.ShapeInfo.line(start.x, start.y, end_x, end_y);
            //
            //Get the intersections of the line and circles
            const intersection1 = KldIntersections.Intersection.intersect(ellipse, line);
            //
            //Retrieve the intersection 
            const p1 = intersection1.points[0]; 
            //
            //Return the intersection point 
            return p1;
    }
    //
    //Drags the circle that shows the relation
    circle_drag(circle, point){
        //
        circle.setAttribute('cx', point.x);
        circle.setAttribute('cy', point.y);

    }

} 