
//Resolve references to the schema namespace
import * as schema from "../../../schema/v/code/schema.js";

import {view, page, svgns} from "../../../outlook/v/code/view.js";
//
//THis library helps us to talk to the server in PHP
import * as server from "../../../schema/v/code/server.js"

import * as quest from "../../../schema/v/code/questionnaire.js";

//The metavisuo application class
export class metavisuo extends page {
    //
    public current_db?: database;
    // 
    //class constructor.
    constructor() {
        super();
    }

    //
    //Generate the structure from the given named database among the list of all 
    //available databases and draw its visual structure
    async get_metadb(dbname: string): Promise<database> {
        //
        //Generate an Idatabase structure for the selected database. Ensure that
        //a complete database is generated and that no exceptions should be 
        //thrown if the datanase has a problem
        const structure: schema.Idatabase = await server.exec(
            "database", 
            [dbname, true, false], 
            "export_structure", 
            []
        );
        //
        //Use the generated schema.Idatabase to generate a database structure
        const dbase = new schema.database(structure);
        //
        //Get the element where to hook the svg element
        const content = this.get_element('content');
        //
        //Create the database structure to visualize
        return new database(content, dbase);
    }
    //
    //Populate the selector designated to hold all the named databases on 
    //this server and return the selector
    populate_selector(databases: Array<string>): HTMLSelectElement {
        //
        //Get the selector element
        const selector = <HTMLSelectElement> this.get_element("databases");
        //
        // Sort the databases in an alphaetical order when the selector is loaded
        databases.sort();
        //
        //For each database name create a selector option and add it to the selector
        databases.forEach(dbname => this.create_element("option", selector, {
            textContent: dbname,
            value: dbname
        }));
        //
        //Rteurn teh selector
        return selector;
    }
    //
    //Fetch all databases saved within the MYSQL database structure
    async get_databases(): Promise<Array<string>> {
        //
        //Construct the query to extract all databases except mysql, performance_schema,phpmyadmin
        //sys, and information schema
        const sql:string = 
            `select 
                schema_name as dbname 
             from 
                information_schema.schemata
             where
                schema_name not in (
                    'mysql','performance_schema', 'sys','information_schema','phpmyadmin'
                )
            `
        //
        //Construct and execute the query to list all databases using the 
        //information schema
        const dbases: Array<{dbname: string}> = await server.exec("database", ["information_schema"], "get_sql_data", [sql]);
        //
        //Return the compiled list of database names
        return dbases.map(db=>db.dbname);
    }
    // 
    //
    //Get all databases in the system, populate the selector and pick
    //the first database
    public async show_panels(): Promise<void> {
        //
        // Get the databases
        const databases: Array<string> = await this.get_databases();
        //
        //Alert the user (and discontinue this show) if there are no databases
        if (databases.length === 0) {alert('No databases are found');return}
        //
        // Populate the database slector 
        const selector:HTMLSelectElement= this.populate_selector(databases);
        //
        // Get the database from the local storage if any
        const current_dbname = localStorage.getItem("current_dbname");
        //
        // If the stored dbname exists in the local storage,select in the dropdown
        if(current_dbname){selector.value = current_dbname;} 
        //
        // Add a listener to show a selected database
        selector.onchange = async () => await this.show_dbase();
        //
        // show the db
        await this.show_dbase();
    }

    //Show the selected database. 
    async show_dbase(): Promise<void> {
        //
        //Remove the current database, if any
        if (this.current_db !== undefined) this.current_db.hook.removeChild(this.current_db.svg);
        //
        //Get the selected database. For you to get here, there must be one. 
        const dbname: string = this.get_selected_value("databases");
        //
        //Store the database in the local storage to be used later in the loading
        //of the page the first time
        localStorage.setItem("current_dbname", dbname);
        //
        //Get the metavisuo database -- an extension of the schema.database
        this.current_db = await this.get_metadb(dbname);
        //
        //Draw the entities and relationships
        await this.current_db.draw();
    }
}

//A metavisual database extends the schema version
export class database extends schema.database{
    //
    //The entities of the current application database.
    public entities: {[index: string]: entity};
    //
    //Collection of (unindexed) raltons for thos entity
    public relations: Array<relation>;
    // 
    //Set the view box properties.
    //
    //Set the panning attributes of a view box.
    public panx: number = 0;
    public pany: number = 0;
    //
    //Set the scaling attributes of a view box.
    public zoomx: number = 128;
    public zoomy: number = 64;
    //
    //The database name that holds the metadata; its either this database -- if
    //the metadata is embeded, or the standalone metavisuo database
    public meta_dbname?:string;
    // 
    //class constructor.
    constructor(
        //The HTML tag where to hook the svg element for this database
        public hook: HTMLElement,
        //
        //The schema database that is the extension of this meta-visuo version is
        //the one to which  
        public dbase: schema.database
    ) {
        //A database is the highest object in the matavisuo hierarchy and 
        //therefore has no parent
        super(dbase.static_dbase);
        //
        //Prepare to set the SVG element
        // 
        //Create the svg element in our content element in the html file. 
        //N.B. The schema class uses a getter to make this element available
        //to all the children of a database, i.e., entities, attrbutes and relations 
        this.svg = this.document.createElementNS(svgns, "svg");
        //
        //Attach the svg to the hook.
        hook.appendChild(this.svg);
        //
        //Add an event listener for moving the entity group to the double clicked position.
        this.svg.ondblclick = (ev) => this.entity_move(ev);
        // 
        //Add the view box attribute, based on the zoom and pan settings.
        this.svg.setAttribute("viewBox", `${[this.panx, this.pany, this.zoomx, this.zoomy]}`);
        //
        //Add the zooom out event listener to the zoom_out button
        this.get_element('zoom_out').onclick = () => this.zoom('out');
        this.get_element('zoom_in').onclick = () => this.zoom('in');
        // 
        //Add the pan_left,pan_right,pan_up and pan_down event listener button.
        this.get_element('pan_left').onclick = () => this.pan('left');
        this.get_element('pan_right').onclick = () => this.pan('right');
        this.get_element('pan_up').onclick = () => this.pan('up');
        this.get_element('pan_down').onclick = () => this.pan('down');
        //
        //Get the save button for adding an event listener
        this.get_element('save').onclick = async () => await this.save();
        //
        //Pan the documents in view, depending on the selected keys
        //Add a test key press event
        onkeydown = (ev)=>this.pan_with_keys(ev); 
        //
        //Create arrow markers, e.g., the crawfoot for relationships,
        this.create_markers();
        //
        //Create the meta-visuo entities
        this.entities = this.create_entities(dbase);
        //
        //Create and collect the meta_visuo relations 
        this.relations = [...this.collect_relations(dbase)];
    }
    
    //The viual representative of a database, i.e., proxy,  is the svg element
    get proxy():SVGElement{return this.svg; }
    
    //Pan using the keyboard
    pan_with_keys(event: KeyboardEvent): void {
        //
        //Use the event code to pan
        switch (event.code) {
            case "ArrowRight": this.pan('right'); break;
            case "ArrowLeft": this.pan('left'); break;
            case "ArrowUp": this.pan('up'); break;
            case "ArrowDown": this.pan('down'); break;
            default:
        }
    }

    //Create arrow markers, e.g., the crawfoot for relationships,
    create_markers(): void {
        //
        //Define the marker paths
        const paths:{[key:string]:string}= {
            //
            foot_optional: 'M 30 32, L-18 32,M 10 22, L 30 22 M 10 42, L 30 42 M 10 22 L 10 42',
            //
            foot_optional_identifier:'M 30 32, L-18 32,M 10 22, L 30 22 M 10 42, L 30 42 M 10 22 L 10 42,M -7 16 L-16 42.6 M -32 -2 L-4.5 42.6',
            //
            foot_manda_identifier:'M 30 32, L-18 32,M 10 22, L 30 22 M 10 42, L 30 42 M 10 22 L 10 42,M 1 16 L1 44 M -7 16 L-16 42.6 M -32 -2 L-4.5 42.6 ',
            //
            foot_mandatory:'M 30 32, L-18 32,M 10 22, L 30 22 M 10 42, L 30 42 M 10 22 L 10 42,M 1 16 L1 44 ',
            //        
            tick:'M 30 30 L 30 44',
            //
            arrow:'M 8 8 L 0 4 L 0 12'
        };
        //
        //Draw the marker correspondig to each path
        for(const key in paths) this.draw_marker(key, paths[key])
    }
    //
    //Draw the named marker using the given path
    draw_marker(key:string, path_str:string):void{
        //
        //DRAW THE LINE  MARKER
        // Create the marker element for the attributes.
        const marker: SVGMarkerElement = <SVGMarkerElement> document.createElementNS(svgns, "marker");
        // 
        //Attach the marker to the svg element
        this.svg.appendChild(marker);
        // 
        // Supply the marker attributes
        //
        //Define the marker view box
        const panx: number = -20;
        const pany: number = -20;
        // 
        //Set the width of the viewport into which the <marker> is to be fitted when it is 
        //rendered according to the viewBox
        const realx: number = 64;
        // 
        //Set the height of the viewport into which the <marker> is to be fitted when it is 
        //rendered according to the viewBox 
        const realy: number = 64;
        //
        //Marker size (pixels)
        //Set the height of the marker
        const tickheight: number = 20;
        // 
        //Set the width of the marker
        const tickwidth: number = 20;
        //
        //Set the marker view box
        marker.setAttribute("viewBox", `${[panx, pany, realx, realy]}`);
        //
        //Set the name of the marker
        marker.setAttribute("id", key);
        //
        //Set the reference point for the marker to be the center of the viewbox
        //Define the x coordinate of the marker referencing point
        marker.setAttribute("refX", `${0.5 * realx}`);
        // 
        //Define the y coordinate of the marker referencing point
        marker.setAttribute("refY", `${0.5 * realy}`);
        marker.setAttribute("markerWidth", `${tickwidth}`);
        marker.setAttribute("markerHeight", `${tickheight}`);
        //
        marker.setAttribute("orient", "auto-start-reverse");
        //
        //Trace the path that defines this marker
        const path_element: SVGPathElement = this.document.createElementNS(svgns, "path");
        path_element.setAttribute('d', path_str);
        path_element.classList.add('chickenfoot');
        // 
        // Attach the line marker to the marker element
        marker.appendChild(path_element);
    }

    //Zoming out is about increasing the zoom x an y components of this database
    //by some fixed percentage, say 10%
    zoom(dir: 'in' | 'out'): void {
        //
        // 
        const sign = dir === 'in' ? +1 : -1;
        //
        //Change the database zooms
        this.zoomx = this.zoomx + sign * this.zoomx * 10 / 100;
        this.zoomy = this.zoomy + sign * this.zoomy * 10 / 100;
        //
        this.svg.setAttribute("viewBox", `${[this.panx, this.pany, this.zoomx, this.zoomy]}`);
    }
    // 
    //
    pan(dir: 'up' | 'left' | 'right' | 'down'): void {
        //
        //Determine x, the amount by which to pan x, as 5% of 132
        const x = 5 / 100 * 132;
        //
        //Detemine y,the amount by which to pan y, as 5% of 64
        const y = 5 / 100 * 64;
        //
        //Determine the pan direction and make the necessary pan
        //property changes
        switch (dir) {
            case 'up':
                //
                //Change the pany by some positive amount (y)
                this.pany = this.pany + y;
                //
                //Limit the diagram in view to the view,i.e., such that it is not hidden from the view
                if (this.pany > 50) {
                    //
                    //Alert the user that the document might be getting out of view
                    alert("This document is out of view, move down or zoom out to view it");
                    //
                    //Prevent the user from moving further out of view
                    return;
                }
                break;
            case 'down':
                //    
                //Change pany y with some negative amount (y)
                this.pany = this.pany - y;
                //
                //Limit the diagram in view to the view,i.e., such that it is not hidden from the view
                if (this.pany < -50) {
                    //
                    //Alert the user that the document might be getting out of view
                    alert("This document is out of view, move up or zoom out to view it");
                    //
                    //Prevent the user from moving further out of view
                    return;
                }
                break;
            case 'left':
                //
                //Change the pan x with some positive amount (x)
                this.panx = this.panx + x;
                //console.log(this.panx);
                //
                //Limit the diagram in view to the view,i.e., such that it is not hidden from the view
                if (this.panx > 50) {
                    //
                    //Alert the user that the document might be getting out of view
                    alert("This document is out of view, move right or zoom out to view it");
                    //
                    //Prevent the user from moving further out of view
                    return;
                }
                break;
            case 'right':
                //Change panx with some negative amount (x)
                this.panx = this.panx - x;
                //
                //Limit the diagram in view to the view,i.e., such that it is not hidden from the view
                if (this.panx < -50) {
                    //
                    //Alert the user that the document might be getting out of view
                    alert("This document is out of view, move left or zoom out to view it");
                    //
                    //Prevent the user from moving further out of view
                    return;
                }
                // this.panx +=x;
                break
        }
        //
        //Effect the changes
        this.svg.setAttribute("viewBox", `${[this.panx, this.pany, this.zoomx, this.zoomy]}`);

    }

    //Create the metavisuo entiies
    create_entities(dbase: schema.database): {[index: string]: entity} {
        //
        //Start with an empty collection of entites
        const entities: {[index: string]: entity} = {};
        //
        //
        //Loop over all schema entities and convert them to metavisuo versions, saving and 
        //drawing them at the same time
        for (const ename in dbase.entities) {
            //
            //Create the meta-visuo entity (with default, i.e., random, xand y coordinates)
            const ent = new entity(this, ename);

            //Save the newly created entity to the metavisuo entities.
            entities[ename] = ent;
        }
        //
        //Return the constructed entities
        return entities;
    }
    //
    //Save the entity coordinates to the database
    async save(): Promise<void> {
        //
        //Collect all the labels for saving the x and y coordinates to a database
        const layouts: Array<quest.layout> = [...this.collect_labels()];
        //
        //Execute the loading of layouts
        const result: 'Ok' | string = await server.exec(
            'questionnaire',
            [this.meta_dbname!],
            'load_common',
            [layouts]
        );
        //
        //Report the result
        alert(result);
    }

    //Collect all the label layouts needed for saving the status of the this
    //database
    *collect_labels(): Generator<quest.label> {
        //
        //The name of teh databse
        yield [this.name, 'dbase', 'name'];
        //
        //Save the current pan and zoom values to the 
        yield [this.panx, 'dbase', 'pan_x'];
        yield [this.pany, 'dbase', 'pan_y'];
        yield [this.zoomx, 'dbase', 'zoom_x'];
        yield [this.zoomy, 'dbase', 'zoom_y'];
        //
        //For each entity, generate labels for saving the x/y cordinates 
        for (const key in this.entities) {
            //
            //Get the entity
            const entity: entity = this.entities[key];
            //
            yield [entity.name, 'entity', 'name', [entity.name]];
            yield [entity.x, 'entity', 'x', [entity.name]];
            yield [entity.y, 'entity', 'y', [entity.name]];
        }
    }

    // 
    //Draw the database entities and relations
    async draw(): Promise<void> {
        //
        //Set the database that contains the metata. It's either this one, if 
        //the metadata subsystem are embedded, or the external one, metavisou
        this.meta_dbname = this.entities['dbase']===undefined  ? 'metavisuo':this.name;;
        //
        //Load the position data for the entities from the database
        await this.load_x_y_positions();
        //
        //Ovrride the default zoom and pan settings with those from the database
        //await this.load_viewbox();
        //
        //Draw the entities
        for (const ename in this.entities) this.entities[ename].draw();
        // 
        //Draw the relationships.
        this.relations.forEach(Relation => Relation.draw());
    }

    //Load the entities' x and y coordinates from the metavisuo database
    async load_x_y_positions(): Promise<void> {
        //
        //Set the x and y coordinates
        //
        //Compile the sql
        const sql: string =
            `select
                entity.name,
                entity.x,
                entity.y
             from
                entity
                inner join dbase on entity.dbase = dbase.dbase
             where
                dbase.name = '${this.name}'   
            `
        //
        //Retrieve the data 
        const result: Array<{name: string, x: number, y: number}> = await server.exec
        ('database', 
        [`${this.meta_dbname}`], 
        'get_sql_data', 
        [sql]);
        //
        //Use the result to set the x and y coordinates for the matching entity
        //in this database
        result.forEach(row => {
            const entity = this.entities[row.name];
            entity.x = row.x;
            entity.y = row.y
        });
    }
    //
    //Loop over all metavisuo entities and extract the foreign keys. For each 
    //foreign key that is not external, collect it as a relation
    *collect_relations(dbase: schema.database): Generator<relation> {
        // 
        //For each metavisuo entity step throroug all here columns
        for (const ename in dbase.entities) {
            //
            //Get the named entity
            const entity: schema.entity = dbase.entities[ename];
            //
            //Get the columns of the entity as an array
            const columns: Array<schema.column> = Object.values(entity.columns);
            // 
            //For each foreign key that is pointing to an entity in this database
            //external, collect it as a relatioon
            for (const col of columns) {
                //
                //Only foreign key columns are considered
                if (!(col instanceof schema.foreign)) continue;
                //
                //External relations are not considered. They will be treate
                //as special attributes
                if (col.ref.dbname !== this.dbase.name) continue;
                //
                //Get the source (home) meta_visuo.entity
                const src: entity = this.entities[ename];
                //
                //Use the foreign key to define a relation.
                yield new relation(col, src);
            }
        }
    }
    
    // 
    //Move the selected entity to the double-clicked position
    entity_move(ev: MouseEvent): void {
        //
        //Get the selected entity
        //
        //Get the group that corresponds to the selected entity
        const group = <SVGGraphicsElement | null> this.svg.querySelector('.selected');
        //
        //If there is no selection then discontinue the move
        if (group === null) return;
        //
        //Get the name of the entity; it is the same as the id of the group
        const ename: string = group.id;
        //
        //Get the named entity
        const entity: entity = this.entities[ename];
        //
        //Get the coordinates of the double-clicked position (in real units). 
        //The grop element provided access to the CTM. Could we have gotten it 
        //using this.svg Element?
        const position: DOMPoint = this.entity_get_new_position(ev, group);
        //
        //Transger control to the entity being moved
        entity.move(position);
    }

    // 
    //Get the coordinates of the double-clicked position (in real units), given 
    //the event generated by the event.
    entity_get_new_position(ev: MouseEvent, element: SVGGraphicsElement): DOMPoint {
        //
        //Get the mouse coordinates (in pixels) where the clicking occured on 
        //the canvas. 
        const x: number = ev.clientX;
        const y: number = ev.clientY;
        //
        //Convert the mouse pixel coordinates to the real world coordinates, 
        //given our current viewbox
        //
        //Use the x and y pixels to define an svg point
        const point_old: DOMPoint = new DOMPoint(x, y);
        //
        //N.B. There are 2 methods for getting Client Transformatiom Matrices, 
        //viz., sceermCTM and clientCTM. After our investigaion, the screen one
        //(contrary to our expectation) gave the correct result. Why????
        const ctm: DOMMatrix | null = element.getScreenCTM();
        //
        //If the ctm is null, then something is unusual. CRUSH
        if (ctm === null) throw 'A null dom matrix was not expected';
        //
        //BUT we want pixels to real world, i.e., the inverse of the CTM
        const ctm_inverse: DOMMatrix = ctm.inverse();
        //
        //Use the inverse matrix of the CTM matrix to transform the old point to new one
        const point_new: DOMPoint = point_old.matrixTransform(ctm_inverse);
        //
        return point_new;
    }
}

// 
//The entity in the meta-visuo namespace is an extension of the schema version
class entity extends schema.entity{
    //
    //The position of this entity in the e-a-r drawing
    public x: number;
    public y: number;
    //
    //The radius of the circle that defines our entity
    radius: number = 5;
    //
    //The angle of the attributes
    angle: number = 0;
    //
    //The visual dimenion of this entity
    proxy:SVGGraphicsElement;
    //
    //The attributes of this entity
    attributes: Array<attribute>;
    //
    constructor(
        //
        //The metavisuo database
        public dbase: database,
        //
        //The entity name
        public name: string,
        //
        //The center of the circle that represents this entity. If the coordinates
        //are not known, random values will be used 
        x?: number,
        y?: number
    ) {
        //
        //The (visual) parent of an entity is a database 
        super(dbase, name);
        // 
        // Create the entity group tag that represents its visual aspect 
        this.proxy = this.document.createElementNS(svgns, "g");
        //
        //Mark this as an entity
        this.proxy.classList.add('entity');
        //
        //Attach this proxy to that of the database to establish teh visual 
        //relationship
        this.dbase.proxy.appendChild(this.proxy);
        //
        //Set the x and y value to to either the given values or a random number
        this.x = x === undefined ? dbase.zoomx * Math.random() : x;
        this.y = y === undefined ? dbase.zoomy * Math.random() : y;
        //
        //Collect the attributes of this entity
        this.attributes = [...this.collect_attributes()];
    }

    //Collect the attributes of this entity
    *collect_attributes():Generator<attribute>{
        //
        ///Loop through all the columns of this entity
        for (const col of  Object.values(this.columns)){
            //
            //Consider ordinary attributes
            if (col instanceof schema.attribute) yield new attribute(this, col);
            //
            //Consider foreign key columns tha point to external entities, i.e.,
            //those that are in the same database as this entity
            if (col instanceof schema.foreign && col.ref.dbname!==col.entity.dbase.name){
                //
                //Use tey column to create an attribute
                const attr = new attribute(this, col);
                //
                //Add a special class to the attribute
                attr.proxy.classList.add('external');
                //
                yield attr;
            }
        }
    }

    //Draw this  entity as a circle with attributes at some angle
    draw(): entity {
        //
        //Draw the circle of the entity and return the circle element
        const circle: SVGCircleElement = this.draw_circle();
        // 
        //Draw the labels of the entity and return an element under which all 
        //the labeling elements are grouped
        const attributes: SVGElement = this.draw_attributes();
        //
        //Draw the entity text and return the text element
        const text: SVGTextElement = this.draw_text(this.name, this.x, this.y);
        //
        //Group the elements that define an entity
        this.draw_group(circle, attributes, text);
        //
        //Return this entity
        return this;
    }

    //Draw the circle that represents the entity 
    draw_circle(): SVGCircleElement {
        //		
        //Create the circle element to represent an entity  
        const circle: SVGCircleElement = document.createElementNS(svgns, "circle");
        // 
        //Attach the circle to the svg element
        this.svg.appendChild(circle);
        // 
        // Set the x coordinate of the centre of the circle
        circle.setAttribute("cx", `${this.x}`);
        // 
        // Set the y coordinate of the centre of the circle
        circle.setAttribute("cy", `${this.y}`);
        // 
        // Set the circle radius.
        circle.setAttribute("r", `${this.radius}`);
        //
        //If there are errors in this  entity mark it as such
        if (this.errors.length>0) circle.classList.add('error');
        //
        return circle;
    }
    
    
    // 
    //Create an element that puts the entity circle, labels and text into a 
    //single group
    draw_group(circle: SVGCircleElement, labels: SVGElement, text: SVGTextElement):void {
        //
        //Use the group that repseremts the visual aspect of this entity
        const group = this.proxy;
        //
        //Assign the group id, to match the entity being created
        group.id = this.name;
        // 
        //Attach the circle, labels and text elements to the entity group
        group.append(circle, labels, text);
        //
        //Attach the entity group to the database's svg
        this.svg.appendChild(group);
        //
        //Add an event listener such that when this entity is clicked on, the selection is  
        //removed from any other entity that is selected and this becomes selected 
        group.onclick = () => this.select();
        
    }
    // 
    // Draw the name of the entity on the diagram
    draw_text(name: string, centerx: number, centery: number): SVGTextElement {
        // 
        // Create the text element to representan entity
        const text: SVGTextElement = document.createElementNS(svgns, "text");
        // 
        // Attach the text to the svg element
        this.svg.appendChild(text);
        // 
        // Set the x and y coordinates of the text
        text.setAttribute("x", `${centerx}`);
        text.setAttribute("y", `${centery}`);
        //
        text.setAttribute("class", 'lables');
        // 
        // Set the text position of the entity
        text.setAttribute("text-anchor", "middle");
        text.textContent = (`${name}`);
        //
        return text;
    }

    // 
    // Draw the attributes of this entity
    draw_attributes(): SVGElement {
        // 
        //Create a group tag for placing all our attributes.
        const gattr:SVGElement = this.document.createElementNS(svgns, "g");
        // 
        //Attach the group element to the svg tag.
        this.proxy.appendChild(gattr);
        // 
        // Rotate the g tag about the center according to the suggested angle. 
        gattr.setAttribute("transform", `rotate(${this.angle},${this.x}, ${this.y})`);
        // 
        //The class is necessary for styling
        gattr.setAttribute('class', 'gattribute');
        // 
        //B. Create the polyline that is the backbone of the attributes
        //
        //Create the polyline element 
        const poly: SVGPolylineElement = document.createElementNS(svgns, "polyline");
        // 
        //Attach the polyline to the svg element
        gattr.appendChild(poly);
        //
        //Get the points that define the polyline segments, in the format of e.g., 
        // ["3,40" "5,36" "9,32"]
        const values: Array<string> = this.attributes.map((lables, i) => 
            `${this.x}, ${this.y - this.radius - 2 * i}`
        );
        // 
        //Join the values with a space separator 
        const points: string = values.join(" ");
        // 
        //Define the polyline segments 
        poly.setAttribute('points', points);
        // 
        //The class to be provided in order to style the attribute hosting the 
        //attributes
        poly.setAttribute('class', 'attrpoly');
        //
        //Attach the markers to the polyline segments, assuming that we have 
        //defined a marker by that name
        poly.setAttribute("marker-mid", "url(#tick)");
        poly.setAttribute("marker-end", "url(#tick)");
        // 
        //C. Create a tag for grouping the text elements that represent the 
        //attribute names, so that we can control the  positioning, especially 
        //the top and bottom margins
        const gtext = document.createElementNS(svgns, "g");
        //
        //Attach the text group tag to the parent attribute group
        gattr.appendChild(gtext);
        // 
        //Defining the top and left margins of the text labels
        const left: number = 1;
        const top: number = 0.5;
        //
        // Provide top and and left margins for the text labels  
        gtext.setAttribute("transform", `translate(${left},${top})`);
        //
        //For each attribute name, draw its label
        this.attributes.forEach((attribute, i) => attribute.draw(i, gtext));
        //
        //Return the attribute group
        return gattr;
    }
    // 		
    //Mark this entity as selected
    select() {
        //
        //Get the entity that was previously selected
        const previous: HTMLElement | null = this.svg.querySelector('.selected');
        //
        //If there is any, deselect it
        if (previous !== null) previous.classList.remove('selected');
        //
        //Mark this entity as selected
        this.proxy.classList.add('selected');
    }
    // 
    //Move this entity from the current position to the given one
    move(position: DOMPoint): void {
        //
        //Update the cordinates of this entity with the new position
        this.x = position.x;
        this.y = position.y;
        //
        //Remove from the proxy (i.e., the visual element for this entity) from 
        //its parent
        this.dbase.proxy.removeChild(this.proxy);
        //
        //5. Re-draw the selected entity such that the center of the entity's circle
        //lies at the double clicked position
        this.draw();
        //
        //Clear all relations
        this.dbase.relations.forEach(Relation => Relation.clear());
        //
        //Draw all relations
        this.dbase.relations.forEach(Relation => Relation.draw());
        //
        //Mark the entity as selected
        this.proxy.classList.add('selected');
    }
}


//A metavisuo attribute extends the schema version
class attribute extends schema.attribute{
    //
    //Redfeine attributes to match those of metavisuo
    declare attributes:Array<attribute>;
    //
    proxy:SVGTextElement;
    //
    //Note how we  have generalized teh definition of a visual attrobute beyond 
    //that of s schema, so that we can regard foreign keys that reference a 
    //database external to this one as metavisual attribute  
    constructor(public entity:entity, col:schema.column){
        super(entity, col.static_column);
        // 
        //Create a group tag for placing all our attributes.
        this.proxy = this.document.createElementNS(svgns, "text");
    }

    // 
    //Draw the given label at the given index position
    draw(
        //
        //The index of the label from the base 
        index: number,
        //
        //Where to place the label -- the group that contains the attributes 
        //and the line segments together 
        gtext: Element,

    ): void {
        // 
        //Append the label text element to the gtext group element
        gtext.appendChild(this.proxy);

        //Set the x coordinate to the fixed value of x
        this.proxy.setAttribute("x", `${this.entity.x}`);
        //
        //Set the y coordinate to the radius plus 1 units from the center minus index times 4
        this.proxy.setAttribute("y", `${this.entity.y - this.entity.radius - 2 * index}`);
        //
        //Set the name of the label
        this.proxy.textContent = this.name;
        //
        //Add apearances repesenting....
        const list:DOMTokenList = this.proxy.classList;

        //Mark attributes that have  errors
        if (this.errors.length>0) list.add('error');
        //
        //Mark attributes whose uasge is mandatory
        if (this.is_nullable!=='YES') list.add('mandatory')
        //
        //Mark attributes that are used for indentification
        if (this.is_id()) list.add('indentifier')
    }
}

//
//A metavisuo relation is an extension of a schema foreign key column
class relation extends schema.foreign{
    // 
    //The the svg element taht represents the visual aspect of this relationship
    public proxy:SVGElement;
    // 
    constructor(public col:schema.foreign, public entity:entity) {
        //
        super(entity, col.static_column);
        //
        //Collect all the possible errors associated with this relation, to 
        //include additinal ones that be may derived in this class
        this.errors = [...this.errors, ...this.collect_errors()];
        //
        //Create the visual representative of this relation. This is where the
        //polyline will be hooked
        this.proxy = this.document.createElementNS(svgns, 'g');
        this.entity.proxy.appendChild(this.proxy);
    }

    //
    //Get the source and destination entities of this relation
    get src():entity {return this.entity};
    get dest():entity {return this.entity.dbase.entities[this.ref.ename]}
    
    //Collecta additional errors found in a relation beyond this inherited fom 
    //the column used in its construction
    *collect_errors():Generator<Error>{
        //
        //Cyclic definitions are invalid definitions
        const {dbname, ename} = this.col.ref;
        if (dbname===this.dest.dbase.name  && ename===this.dest.name) yield new Error("This relation is cyclic");
        //
        //If a relation is not hierarchical and its column name does not match 
        //that of the referenced entity, then this is not comformant to the
        //standar way of expressing joins, i.e., x join y on x.y==y.y will not
        //work if this rule is not observed
        if (!this.col.is_hierarchical && ename!==this.dest.name) yield Error("Referenced source column and destination entity names are different");
    }

    //
    //Draw the relation between the source and the destination entities
    draw(): void {
        //
        //Get the 3 points that define the relation betweeen the source  and
        //the destination entities, e.g., {start:{x:4,y:50}, mid:{x:7, y:10}, end:{x:40, y:19}}
        const {start, mid, end} = this.get_relation_points(this.src, this.dest);
        //
        //Express the points in the form required for a polyline, e.g., 4,50 7,10 40,19 
        const p1 = `${start.x},${start.y}`;
        const p2 = `${mid.x}, ${mid.y}`;
        const p3 = `${end.x},${end.y}`;
        //
        // Create the polyline element
        const polyline: SVGPolylineElement = <SVGPolylineElement> document.createElementNS(svgns, "polyline");
        // 
        //Attach the polyline to the visual svg element
        this.proxy.appendChild(polyline);
        // 
        //Set the polyline's points
        polyline.setAttribute('points', `${p1} ${p2} ${p3}`);
        // 
        //Attach the marker to the middle point of the polyline. Please ensure that
        //the marker named arrow is available. How? By executing the marker drawing code
        polyline.setAttribute("marker-mid", "url(#arrow)");
        polyline.setAttribute("marker-start", `url(#${this.get_marker_name()})`);
        // 
        //The class that will style the lines showing the relations. 
        polyline.classList.add('relations');
        //
        //If there are errors in this relation, then mark it as such
        if (this.errors.length>0) polyline.classList.add('error');
    }
    
    //Get teh name of the marker, depending on the type of this relation
    get_marker_name():string{
        //
        //Get the whether the relation is optional or not
        const optional:boolean = this.is_nullable==='YES';
        //
        //Get whether the relation is used for identification or not
        const id:boolean = this.is_id();
        //
        //Determine the type of chicken foot dependning on the 2 variables:optional anf
        //id
        switch(optional){
            case true:
                switch(id){
                    case true: return "foot_optional_identifier";
                    case false:return "foot_optional";
                }    
            case false:
                switch(id){
                    case true: return "foot_manda_identifier";
                    case false:return "foot_mandatory";
                }
        }
    }    
    
    //
    //Clear a relation
    clear(): void {
        //
        this.entity.proxy.removeChild(this.proxy);
    }
    // 
    //The second version of calculating the exact mid point
    //
    //There are 3 points of interest along the hypotenuse between source entity a and 
    // source entity b, viz.,start, mid and end.
    get_relation_points(a: entity, b: entity): {start: DOMPoint, mid: DOMPoint, end: DOMPoint} {
        //
        //IN MOST CASES, when the x coordinate of circle 1 is equivalent to the x-coordinate
        // of circle 2, then we have a zero difference that will be carried forward to be
        // evaluated later on, will return values of infinity or zero later on.
        //
        //To prevent this from happening, if the difference,i.e., (b.y - a.y) or (b.x - a.x) is 
        //zero, set it to be greater than zero,i.e., 0.1 or greater.
        //
        //
        let opposite: number;
        //
        //The 'opposite' is the y distance between a and b
        //const opposite:number= b.y - a.y;
        if ((b.y - a.y) !== 0) {
            opposite = b.y - a.y;
        }
        else {
            opposite = 0.1;
        }
        let adjacent: number;
        //
        //The 'adjacent' is the x distance between the source entity of a and destination entity b
        //const adjacent = b.x - a.x;
        if ((b.x - a.x) !== 0) {
            adjacent = b.x - a.x;
        }
        else {
            adjacent = 0.1;
        }
        //
        //The hypotenuse is the square root of the squares of the 'adjacent' and 
        //the 'opposite'
        const hypotenuse = Math.sqrt(adjacent * adjacent + opposite * opposite);
        //
        //The targent of thita is calculated by 'oppposite' divided by the 'adjacent'
        const tanthita = opposite / adjacent;
        //
        //Thita is the inverse of the 'tanthita'
        const thita: number = Math.atan(tanthita);
        //
        //The angle of interest is...
        const phi = (adjacent > 0) ? thita : Math.PI + thita;
        //
        //Let 'start' be the point at  the intersection of the entity centered as the source 
        const start = this.get_point(a, phi, a.radius);
        //
        //Let 'mid' be the point mid way along entity source and destination hypotenuse
        const mid = this.get_point(a, phi, 0.5 * hypotenuse);
        //
        //Let 'end' be the point at the intersection of hypotenuse and the entity referred as the  
        //destination
        const end = this.get_point(a, phi, hypotenuse - b.radius);
        //
        //Compile and return the desired final result
        return {start, mid, end};
    }
    // 
    //Returns the coordinates of the point which is 'hypo' units from 'a' along
    //the hypotenuse of a and b (which is inclined at angle thita) 
    get_point(a: entity, thita: number, hypo: number): DOMPoint {
        //
        //The 'opp' is the 'hypo' times the sine of 'thita';
        const opp: number = hypo * Math.sin(thita);
        //
        //The 'adj' is the 'hypo' times the cosine of thita where thita is the
        //angle between 'adj' and 'hypo'
        const adj = hypo * Math.cos(thita);
        //
        //The x coordinate of the mid point is 'adj' units from the center of a
        const x: number = a.x + adj;
        //
        //The y coordinate of the mid point is the 'opp' units from the center of a
        const y: number = a.y + opp;
        //
        //The desired point is at x and and y units from the origin
        return new DOMPoint(x, y);
    }
}   