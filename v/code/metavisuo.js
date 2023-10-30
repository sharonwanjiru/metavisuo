//Resolve references to the schema namespace
import * as schema from "../../../schema/v/code/schema.js";
import { page, svgns } from "../../../outlook/v/code/view.js";
//
//THis library helps us to talk to the server in PHP
import * as server from "../../../schema/v/code/server.js";
//The metavisouo application class
export class metavisuo extends page {
    //
    current_db;
    //
    //A database selector for housing all the databases that are in the server
    selector;
    // 
    //class constructor.
    constructor() {
        super();
    }
    //
    //Generate the structure from the given named database among the list of all 
    //available databases and draw its visual structure
    async get_metadb(dbname) {
        //
        //Generate an Idatabase structure for the selected database. Ensure that
        //a complete database is generated and that no exceptions should be 
        //thrown if the datanase has a problem
        const structure = await server.exec("database", [dbname, true, false], "export_structure", []);
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
    populate_selector(databases) {
        //
        //Get the selector element
        const selector = this.get_element("databases");
        //
        //For each database name create a selector option and add it to the selector
        databases.forEach(dbname => this.create_element("option", selector, {
            textContent: dbname,
            value: dbname
        }));
        //
        //Return teh selector
        return selector;
    }
    //
    //Fetch all names of databases read from the MYSQL information schema
    async get_dbnames() {
        //
        //Extract all database names  except mysql, performance_schema,phpmyadmin
        //sys, and information schema
        const sql = `select 
                schema_name as dbname 
            from 
                information_schema.schemata
            where
                schema_name not in (
                    'mysql',
                    'performance_schema', 
                    'sys',
                    'information_schema',
                    'phpmyadmin'
                )
            order by schema_name    
            `;
        //
        //Retrieve the names
        const dbases = await server.exec("database", ["information_schema"], "get_sql_data", [sql]);
        //
        //Compile and return the list
        return dbases.map(db => db.dbname);
    }
    // 
    //
    //On load, get all databases in the system, populate the selector and pick
    //the first database
    async onload() {
        //
        //Get all the names of the databases available on this server
        const dbnames = await this.get_dbnames();
        //
        //Alert the user (and discontinue this show) if there are no databases
        if (dbnames.length === 0) {
            alert('No databases are found');
            return;
        }
        //
        //Populate the database selector
        this.selector = this.populate_selector(dbnames);
        //
        //Add a listener to show a selected database
        this.selector.onchange = async () => await this.show_dbase();
        //
        //Select the last database
        if (localStorage['last_dbase'])
            this.selector.value = localStorage['last_dbase'];
        //
        //Show the selected database
        await this.show_dbase();
    }
    //On selecting a database, show it; then and save it to the local storage 
    async show_dbase() {
        //
        //Remove the current database, if any
        if (this.current_db !== undefined)
            this.current_db.hook.removeChild(this.current_db.svg);
        //
        //Get the selected database name. For you to get here, there must be one. 
        const dbname = this.get_selected_value("databases");
        //
        //Save the selected database to the local storage
        window.localStorage['last_dbase'] = dbname;
        //
        //Get the metavisuo database -- an extension of the schema.database
        this.current_db = await this.get_metadb(dbname);
        //
        //Show all the the entities and their relationships
        await this.current_db.show();
    }
}
//A metavisual database extends the schema version
export class database extends schema.database {
    hook;
    dbase;
    //
    //The entities of the current application database.
    entities;
    //
    //Collection of (unindexed) relatons for between all the entities
    relations;
    // 
    //Set the view box properties.
    //
    //Set the panning attributes of a view box.
    panx = 0;
    pany = 0;
    //
    //Set the scaling attributes of a view box.
    zoomx = 128;
    zoomy = 64;
    //
    //The database name that holds the metadata; its either this database -- if
    //the metadata is embeded, or the standalone metavisuo database
    get meta_dbname() {
        //
        //Set the database that contains the metata. It's either this one, if 
        //the metadata subsystem are embedded, or the external one, metavisuo
        return this.entities['dbase'] === undefined ? 'metavisuo' : this.name;
    }
    ;
    // 
    //class constructor.
    constructor(
    //The HTML tag where to hook the svg element for this database
    hook, 
    //
    //The schema database that is the extension of this meta-visuo version is
    //the one to which  
    dbase) {
        //A database is the highest object in the matavisuo hierarchy and 
        //therefore has no parent
        super(dbase.static_dbase);
        this.hook = hook;
        this.dbase = dbase;
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
        onkeydown = (ev) => this.pan_with_keys(ev);
        //
        //Create the meta-visuo entities
        this.entities = this.create_entities(dbase);
        //
        //Create and collect the meta_visuo relations 
        this.relations = [...this.collect_relations(dbase)];
        //
        //Create arrow markers, e.g., the crawfoot for relationships,
        this.create_markers();
    }
    //
    //Show all the the entities and their relationships
    async show() {
        //
        //Move all the entities to their designated psoitions
        for (const ename in this.entities)
            this.entities[ename].move();
    }
    //The viual representative of a database, i.e., proxy,  is the svg element
    get proxy() { return this.svg; }
    //Pan using the keyboard
    pan_with_keys(event) {
        //
        //Use the event code to pan
        switch (event.code) {
            case "ArrowRight":
                this.pan('right');
                break;
            case "ArrowLeft":
                this.pan('left');
                break;
            case "ArrowUp":
                this.pan('up');
                break;
            case "ArrowDown":
                this.pan('down');
                break;
            default:
        }
    }
    //Create arrow markers, e.g., the crawfoot for relationships,
    create_markers() {
        //
        //Define the marker paths
        const paths = {
            //
            foot_optional: 'M 30 32, L-18 32,M 10 22, L 30 22 M 10 42, L 30 42 M 10 22 L 10 42',
            //
            foot_optional_identifier: 'M 30 32, L-18 32,M 10 22, L 30 22 M 10 42, L 30 42 M 10 22 L 10 42,M -7 16 L-16 42.6 M -32 -2 L-4.5 42.6',
            //
            foot_manda_identifier: 'M 30 32, L-18 32,M 10 22, L 30 22 M 10 42, L 30 42 M 10 22 L 10 42,M 1 16 L1 44 M -7 16 L-16 42.6 M -32 -2 L-4.5 42.6 ',
            //
            foot_mandatory: 'M 30 32, L-18 32,M 10 22, L 30 22 M 10 42, L 30 42 M 10 22 L 10 42,M 1 16 L1 44 ',
            //        
            tick: 'M 30 30 L 30 44',
            //
            arrow: 'M 8 8 L 0 4 L 0 12'
        };
        //
        //Group all the markers together
        const g = document.createElementNS(svgns, "g");
        g.classList.add('markers');
        this.svg.appendChild(g);
        //
        //Draw the marker corresponding to each path
        for (const key in paths)
            this.draw_marker(key, paths[key], g);
    }
    //
    //Draw the named marker using the given path
    draw_marker(key, path_str, g) {
        //
        //DRAW THE LINE  MARKER
        // Create the marker element for the attributes.
        const marker = document.createElementNS(svgns, "marker");
        // 
        //Attach the marker to the group tag
        g.appendChild(marker);
        // 
        // Supply the marker attributes
        //
        //Define the marker view box
        const panx = -20;
        const pany = -20;
        // 
        //Set the width of the viewport into which the <marker> is to be fitted when it is 
        //rendered according to the viewBox
        const realx = 64;
        // 
        //Set the height of the viewport into which the <marker> is to be fitted when it is 
        //rendered according to the viewBox 
        const realy = 64;
        //
        //Marker size (pixels)
        //Set the height of the marker
        const markerHeight = 5;
        // 
        //Set the width of the marker
        const markerWidth = 5;
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
        marker.setAttribute("markerWidth", `${markerWidth}`);
        marker.setAttribute("markerHeight", `${markerHeight}`);
        //
        marker.setAttribute("orient", "auto-start-reverse");
        //
        //Trace the path that defines this marker
        const path_element = this.document.createElementNS(svgns, "path");
        path_element.setAttribute('d', path_str);
        path_element.classList.add('chickenfoot');
        //Let teh marker scale with the stroke width
        //marker.setAttribute("markerUnits", "strokeWidth");
        //
        //Try this option
        marker.setAttribute("markerUnits", "userSpaceOnUse");
        // 
        // Attach the line marker to the marker element
        marker.appendChild(path_element);
    }
    //Zoming out is about increasing the zoom x an y components of this database
    //by some fixed percentage, say 10%
    zoom(dir) {
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
    pan(dir) {
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
                break;
        }
        //
        //Effect the changes
        this.svg.setAttribute("viewBox", `${[this.panx, this.pany, this.zoomx, this.zoomy]}`);
    }
    //Create the metavisuo entiies
    create_entities(dbase) {
        //
        //Start with an empty collection of entites
        const entities = {};
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
    //Save the entity coordinates to the datanse for saving metadatabas. It is
    //either thie current dbase, if it suports this functionality, or the special
    //one -- metaviuo
    async save() {
        //
        //Collect all the labels for saving the x and y coordinates to a database
        const layouts = [...this.collect_labels()];
        //
        //Execute the loading of layouts
        const result = await server.exec('questionnaire', [this.meta_dbname], 'load_common', [layouts]);
        //
        //Report the result
        alert(result);
    }
    //Collect all the label layouts needed for saving the status of the this
    //database
    *collect_labels() {
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
            const entity = this.entities[key];
            //
            yield [entity.name, 'entity', 'name', [entity.name]];
            yield [entity.position.x, 'entity', 'x', [entity.name]];
            yield [entity.position.y, 'entity', 'y', [entity.name]];
        }
    }
    // 
    //Draw the database entities and relations (as part of the database 
    //construction)
    async draw() {
        //
        //Load the position data for the entities from the database
        await this.load_x_y_positions();
        //
        //Ovrride the default zoom and pan settings with those from the database
        //await this.load_viewbox();
        //
        //Draw the entities
        for (const ename in this.entities)
            this.entities[ename].draw();
        // 
        //Draw the relationships.
        this.relations.forEach(Relation => Relation.draw());
    }
    //Load the entities' x and y coordinates from the metavisuo database
    async load_x_y_positions() {
        //
        //Set the x and y coordinates
        //
        //Compile the sql
        const sql = `select
                entity.name,
                entity.x,
                entity.y
             from
                entity
                inner join dbase on entity.dbase = dbase.dbase
             where
                dbase.name = '${this.name}'   
            `;
        //
        //Retrieve the data 
        const result = await server.exec('database', [`${this.meta_dbname}`], 'get_sql_data', [sql]);
        //
        //Use the result to set the x and y coordinates for the matching entity
        //in this database
        result.forEach(row => {
            const entity = this.entities[row.name];
            entity.position.x = row.x;
            entity.position.y = row.y;
        });
    }
    //
    //Loop over all metavisuo entities and focus on the foreign keys. For each 
    //key that is does notpoint to an external database, collect it as a relation
    *collect_relations(dbase) {
        // 
        //For each metavisuo entity step throug all her columns
        for (const ename in dbase.entities) {
            //
            //Get the named entity
            const entity = dbase.entities[ename];
            //
            //Get the columns of the entity as an array
            const columns = Object.values(entity.columns);
            // 
            //For each foreign key that is pointing to an entity in this database
            //external, collect it as a relatioon
            for (const col of columns) {
                //
                //Only foreign key columns are considered
                if (!(col instanceof schema.foreign))
                    continue;
                //
                //External relations are not considered. They will be treated
                //as special attributes
                if (col.ref.dbname !== this.dbase.name)
                    continue;
                //
                //Get the source (home) meta_visuo.entity
                const src = this.entities[ename];
                //
                //Use the foreign key to define a relation.
                yield new relation(col, src);
            }
        }
    }
    // 
    //Move the selected entity to the double-clicked position
    entity_move(ev) {
        //
        //Get the selected entity
        //
        //Get the group that corresponds to the selected entity
        const group = this.svg.querySelector('.selected');
        //
        //If there is no selection then discontinue the move
        if (group === null)
            return;
        //
        //Get the name of the entity; it is the same as the id of the group
        const ename = group.id;
        //
        //Get the named entity
        const entity = this.entities[ename];
        //
        //Get the coordinates of the double-clicked position (in real units). 
        //The grop element provided access to the CTM. Could we have gotten it 
        //using this.svg Element?
        entity.position = this.entity_get_new_position(ev, group);
        //
        //Effect the move (without redrawing the entity)
        entity.move();
    }
    // 
    //Get the coordinates of the double-clicked position (in real units), given 
    //the event generated by the event.
    entity_get_new_position(ev, element) {
        //
        //Get the mouse coordinates (in pixels) where the clicking occured on 
        //the canvas. 
        const x = ev.clientX;
        const y = ev.clientY;
        //
        //Convert the mouse pixel coordinates to the real world coordinates, 
        //given our current viewbox
        //
        //Use the x and y pixels to define an svg point
        const point_old = new DOMPoint(x, y);
        //
        //N.B. There are 2 methods for getting Client Transformatiom Matrices, 
        //viz., sceermCTM and clientCTM. After our investigaion, the screen one
        //(contrary to our expectation) gave the correct result. Why????
        const ctm = element.getScreenCTM();
        //
        //If the ctm is null, then something is unusual. CRUSH
        if (ctm === null)
            throw 'A null dom matrix was not expected';
        //
        //BUT we want pixels to real world, i.e., the inverse of the CTM
        const ctm_inverse = ctm.inverse();
        //
        //Use the inverse matrix of the CTM matrix to transform the old point to new one
        const point_new = point_old.matrixTransform(ctm_inverse);
        //
        return point_new;
    }
}
// 
//The entity in the meta-visuo namespace is an extension of the schema version
export class entity extends schema.entity {
    dbase;
    name;
    //
    //The position of this entity in the e-a-r drawing
    position;
    //
    //The radius of the circle that defines our entity
    radius = 5;
    //
    //The (slanting) angle of the attributes
    angle = 0;
    //
    //The element that represents the visual dimension of this entity
    proxy;
    //
    //The attributes of this entity
    attributes;
    //
    //The place holder for collected relations connected to this entity. N.B.
    //Relations cannot be determined when an entity is being constructted.
    __relations;
    //
    //The components of an entity
    component;
    //
    //Direct access to this entity's position
    get x() { return this.position.x; }
    get y() { return this.position.y; }
    //
    constructor(
    //
    //The metavisuo database
    dbase, 
    //
    //The entity name
    name, 
    //
    //The center of the circle that represents this entity. If the coordinates
    //are not known, random values will be used 
    position) {
        //
        //The (visual) parent of an entity is a database 
        super(dbase, name);
        this.dbase = dbase;
        this.name = name;
        // 
        // Create the entity group tag that represents its visual aspect 
        this.proxy = this.document.createElementNS(svgns, "g");
        //
        //Mark this as an entity
        this.proxy.classList.add('entity');
        //
        //If there are errors in this  entity mark it as such
        if (this.errors.length > 0)
            this.proxy.classList.add('error');
        //
        //Assign the id, to match the entity being created
        this.proxy.id = this.name;
        //
        //Attach this proxy to that of the database to establish the visual 
        //relationship
        this.dbase.proxy.appendChild(this.proxy);
        //
        //Set the x and y value to to either the given values or a random number
        this.position = position ?? new DOMPoint(dbase.zoomx * Math.random(), dbase.zoomy * Math.random());
        //
        //Draw this entity's componets (before creating entities). N.B. Draw 
        //happens once; move happens many times
        this.component = this.draw();
        //
        //Collect the attributes of this entity (to include foreign key that points
        //to an external database)
        this.attributes = [...this.collect_attributes()];
        //
        //Set te atttributes index, after creation. This was deferred to this 
        //point so that pointers to external databases can be considerd as equal
        //attributes. Otherwise thoer positioning would be problematic
        this.attributes.forEach((attribute, i) => attribute.index = i);
        //
        //Add an event listener such that when this entity is clicked on, the 
        //selection is  removed from any other entity that is selected and this 
        //one
        this.proxy.onclick = () => this.select();
    }
    //The relations of this entity are those that have it --this entity--as 
    //its both the source and the destination.
    *collect_relations() {
        //
        //Visit all the relations of this database
        for (const relation of this.dbase.relations)
            yield* this.collect_relation(relation);
    }
    //Returns the relations of this entity. They are constructed only once.
    get relations() {
        //
        //Return the relations if they are defined
        if (this.__relations)
            return this.__relations;
        //
        //Otherwise derive them from first principles
        this.__relations = [...this.collect_relations()];
        //
        return this.__relations;
    }
    //Collect the given relation if this entity is either its source or its 
    //destination
    *collect_relation(relation) {
        //
        //Collect the given relation if this entity is its source
        if (relation.src === this)
            yield relation;
        //
        //Collect the given relation if this entity is its destination
        if (relation.dest === this)
            yield relation;
    }
    //Collect the attributes of this entity
    *collect_attributes() {
        //
        ///Loop through all the columns of this entity
        for (const col of Object.values(this.columns)) {
            //
            //Consider ordinary attributes
            if (col instanceof schema.attribute)
                yield new attribute(this, col);
            //
            //Consider foreign key columns tha point to external entities, i.e.,
            //those that are in the same database as this entity
            if (col instanceof schema.foreign && col.ref.dbname !== col.entity.dbase.name) {
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
    //Draw this entity as a circle with its attributes slanted at some angle. 
    //This is the arrange layout of the tags:-
    /*
    <g class="entity">....the proxy element
        <circle radius/>
        <text/>

        <!-- The attributes subsytem -->
        <g class="rotatable">
        ...
        </g>
    </g>
    */
    draw() {
        //
        //1. Draw the circle of the entity and return its svg element
        //		
        //Create the circle element to represent an entity  
        const circle = document.createElementNS(svgns, "circle");
        // 
        // Set the circle radius.
        circle.setAttribute("r", `${this.radius}`);
        //
        //Attach the circle to the proxy
        this.proxy.appendChild(circle);
        //
        //2. Draw the entity text
        // 
        // Create the text element to represent this  entity
        const text = document.createElementNS(svgns, "text");
        //
        //Attach the text to the proxy
        this.proxy.appendChild(text);
        // 
        // Center the text at at its (mvable) position
        text.setAttribute("text-anchor", "middle");
        text.textContent = (`${this.name}`);
        // 
        //Draw the attributes sub-system of this entity 
        const attributes = this.draw_attributes();
        //
        return { circle, text, attributes };
    }
    // 
    // Draw the rotatable attributes sub-system . It is organized as follows:-
    /*
    <g class="attributes">

        <polyline mid-marker=... end-marker=.../>

        <g class="margin" transform="translate($left, $top)>

            ...The attribute texts are placed here

        </g
    </g>
    */
    draw_attributes() {
        //
        //A. Prepare the rotaable group of attribute components 
        // 
        //Create a group tag for placing all the attributes subsystem.
        const rotatable = this.document.createElementNS(svgns, "g");
        //
        //Connect the attrobutes to the proxy
        this.proxy.appendChild(rotatable);
        // 
        //The class is necessary for styling
        rotatable.setAttribute('class', 'attributes');
        // 
        //B. Create the polyline that is the backbone of the attribute texts
        //
        //Create the polyline element 
        const polyline = document.createElementNS(svgns, "polyline");
        //
        //Yes, this is the attrobutes backbone
        polyline.classList.add('backbone');
        // 
        //Attach the polyline to the svg element
        rotatable.appendChild(polyline);
        // 
        //Attach the markers to the polyline segments, assuming that we have 
        //defined a marker by the 'tick' id
        polyline.setAttribute("marker-mid", "url(#tick)");
        polyline.setAttribute("marker-end", "url(#tick)");
        // 
        //C. Create a tag for grouping the text elements that represent the 
        //attribute names, so that we can control their  positioning, especially 
        //the top and bottom margins
        const margin = document.createElementNS(svgns, "g");
        margin.classList.add('margin');
        //
        //Attach the margin group to the rotable attrobute group
        rotatable.appendChild(margin);
        // 
        //Define the top and left margins of the text labels
        const left = 1;
        const top = 0.5;
        //
        // Provide top and and left margins for the attribute text labels  
        margin.setAttribute("transform", `translate(${left},${top})`);
        //
        //Return the attribute group
        return { rotatable, polyline, margin };
    }
    //Move to the new entity psoition:-
    //-the components that make up this entoty
    //-the attrobutes of this entoty
    //-the relations attached to this entity   
    move() {
        //
        //1. Move the components that make up this entity to the new position
        //
        //Destructure the componets
        const { circle, text, attributes } = this.component;
        //
        //Move the circle to this entity's position
        circle.setAttribute("cx", `${this.position.x}`);
        circle.setAttribute("cy", `${this.position.y}`);
        //
        //Move the labeling text to this enties position
        text.setAttribute("x", `${this.position.x}`);
        text.setAttribute("y", `${this.position.y}`);
        //
        //Destructure the attributes component to reveal the rotable and polyline
        //elements
        const { rotatable, polyline } = attributes;
        // 
        //Rotate the attributes group about the new entoty location and at
        //suggested angle. 
        rotatable.setAttribute("transform", `rotate(${this.angle},${this.position.x}, ${this.position.y})`);
        //
        //Move the attributes polyline
        //
        //Get the points that define the new polyline segments, in the format of e.g., 
        // ["3,40" "5,36" "9,32"]
        const points = this
            .attributes.map((attribute, i) => `${this.position.x}, ${this.position.y - this.radius - 2 * i}`)
            .join(" ");
        // 
        //Set the polyline segments 
        polyline.setAttribute('points', points);
        //
        //2. Move the attributes of this entity to the new location
        this.attributes.forEach(attribute => attribute.move());
        //
        //3.Move all the relations linked to this entity so that they may
        //strat or end at this entities new position
        this.relations.forEach(relation => relation.move());
    }
    // 		
    //Mark this entity as selected
    select() {
        //
        //Get the entity that was previously selected
        const previous = this.svg.querySelector('.selected');
        //
        //If there is any, deselect it
        if (previous)
            previous.classList.remove('selected');
        //
        //Mark the proxy of this entity as selected
        this.proxy.classList.add('selected');
    }
}
//A metavisuo attribute extends a schema column (not just a schema attribute as
//expected). This allows to represent foreign keys that point to an external
//datanase as attributes
class attribute extends schema.column {
    entity;
    //
    //An attribute has an index, that helps to calculateits  position among its
    //sibblings. N.B. the invalid default position setting must be rectofoed 
    //before an attribute is used. This allowa us to set it after creatting the
    //attribute 
    index = -1;
    //
    proxy;
    //
    //Note how we  have generalized teh definition of a visual attrobute beyond 
    //that of s schema, so that we can regard foreign keys that reference a 
    //database external to this one as metavisual attribute  
    constructor(entity, col) {
        //
        //Initialize the  schema version of an attribute
        super(entity, col.static_column);
        this.entity = entity;
        // 
        //Create a group tag for placing all our attributes.
        this.proxy = this.document.createElementNS(svgns, "text");
        // 
        //Append the proxy of this table tp the given margin element of the 
        //underlying entity
        entity.component.attributes.margin.appendChild(this.proxy);
        //
        //Draw the attribute
        this.draw();
    }
    // 
    //Draw this atttribute, linking it to the margin element of the containing attrinte
    draw() {
        //
        //Set the name of the label
        this.proxy.textContent = this.name;
        //
        //Get a class list to support giving this attribute different appearances
        const list = this.proxy.classList;
        //Mark attributes that have  errors
        if (this.errors.length > 0)
            list.add('error');
        //
        //Mark attributes whose usage is mandatory
        if (this.is_nullable !== 'YES')
            list.add('mandatory');
        //
        //Mark attributes that are used for indentification
        if (this.is_id())
            list.add('indentifier');
    }
    //Move the entity to match the parent entity 
    move() {
        //
        //Set the x coordinate to the fixed value of x
        this.proxy.setAttribute("x", `${this.entity.position.x}`);
        //
        //Set the y coordinate as follows:-
        const y = 
        //
        //Start from the center of the entity
        this.entity.position.y
            //
            //Move upwards by entoty radius units
            - this.entity.radius
            //
            //Move up 1 unit to clear the circle boundary
            - 1
            //
            //Place the label at 2 times its index
            - 2 * this.index;
        //    
        this.proxy.setAttribute("y", String(y));
    }
}
//
//A metavisuo relation is an extension of a schema foreign key column
class relation extends schema.foreign {
    col;
    entity;
    // 
    //The the svg element that represents the visual aspect of this relationship
    proxy;
    //
    //The polyline that represents a relation
    polyline;
    // 
    //A relation is construcructed using data from a foreign key and metavisuo 
    //entity that is its source
    constructor(col, entity) {
        //
        super(entity, col.static_column);
        this.col = col;
        this.entity = entity;
        //
        //Collect all the possible errors associated with this relation, to 
        //include additinal ones that be may derived in this class
        this.errors = [...this.errors, ...this.collect_errors()];
        //
        //Create the visual representative of this relation. This is where the
        //polyline will be hooked
        this.proxy = this.document.createElementNS(svgns, 'g');
        //
        //Link the proxies of both this relation and the given entity. This 
        //ensures that if the entity is hidden, the relation, too, will be hidden
        entity.proxy.appendChild(this.proxy);
        // 
        //The class that will style the lines showing the relations. 
        this.proxy.classList.add('relation');
        //
        //If there are errors in this relation, then mark it as such
        if (this.errors.length > 0)
            this.proxy.classList.add('error');
        //
        //Draw a a relation to return the plyline (which can take part in move 
        //later)
        this.polyline = this.draw();
    }
    //
    //Get the source and destination entities of this relation
    get src() { return this.entity; }
    ;
    get dest() { return this.entity.dbase.entities[this.ref.ename]; }
    //Collecta additional errors found in a relation beyond this inherited fom 
    //the column used in its construction
    *collect_errors() {
        //
        //Cyclic definitions are invalid definitions
        const { dbname, ename } = this.col.ref;
        if (dbname === this.dest.dbase.name && ename === this.dest.name)
            yield new Error("This relation is cyclic");
        //
        //If a relation is not hierarchical and its column name does not match 
        //that of the referenced entity, then this is not comformant to the
        //standar way of expressing joins, i.e., x join y on x.y==y.y will not
        //work if this rule is not observed
        if (!this.col.is_hierarchical && ename !== this.dest.name)
            yield Error("Referenced source column and destination entity names are different");
    }
    //
    //Draw the relation between the source and the destination entities. Return
    //teh same relation, to support chainng
    draw() {
        //
        //Create the plyline compoment of a ralation
        const polyline = this.document.createElementNS(svgns, 'polyline');
        // 
        //Attach the polyline to the visual svg element
        this.proxy.appendChild(polyline);
        // 
        //Attach the arraow marker to the middle point of the polyline. 
        //polyline.setAttribute("marker-mid", "url(#arrow)");
        //
        //Attach a marker at the begining of the polyline, depending on the type
        //of tehe maralion, i.e., optional, mandatory or identifier.
        //N.B. Please ensure that the named markers are available. How? 
        //By executing the marker drawing code
        polyline.setAttribute("marker-start", `url(#${this.get_marker_name()})`);
        //
        return polyline;
    }
    //Move the componnets of a ralation, notably the polyline, to match the
    //source and destinatiopn entity positions
    move() {
        //
        //Get the 3 points that define the relation betweeen the source  and
        //the destination entities, e.g., 
        //{start:{x:4,y:50}, mid:{x:7, y:10}, end:{x:40, y:19}}
        const { start, mid, end } = this.get_relation_points(this.src, this.dest);
        //
        //Express the points in the form required for a polyline, e.g., 4,50 7,10 40,19 
        const p1 = `${start.x},${start.y}`;
        const p2 = `${mid.x}, ${mid.y}`;
        const p3 = `${end.x},${end.y}`;
        // 
        //Set the polyline's points attribute
        this.polyline.setAttribute('points', `${p1} ${p2} ${p3}`);
    }
    //Get teh name of the marker, depending on the type of this relation
    get_marker_name() {
        //
        //Get the whether the relation is optional or not
        const optional = this.is_nullable === 'YES';
        //
        //Get whether the relation is used for identification or not
        const id = this.is_id();
        //
        //Determine the type of chicken foot dependning on the 2 variables:optional anf
        //id
        switch (optional) {
            case true:
                switch (id) {
                    case true: return "foot_optional_identifier";
                    case false: return "foot_optional";
                }
            case false:
                switch (id) {
                    case true: return "foot_manda_identifier";
                    case false: return "foot_mandatory";
                }
        }
    }
    //The second version of calculating the exact mid point
    //
    //There are 3 points of interest along the hypotenuse between source entity a 
    //and destination entity b, viz.,start, mid and end.
    get_relation_points(a, b) {
        //
        //IN MOST CASES, when the x coordinate of circle 1 is equivalent to the 
        //x-coordinate of circle 2, then we have a zero difference that will be 
        //carried forward to be evaluated later on, will return values of 
        //infinity or zero later on.
        //
        //To prevent this from happening, if the difference, i.e., (b.y - a.y) or (b.x - a.x) is 
        //zero, set it to be greater than zero,i.e., 0.1 or greater.
        //
        //
        let opposite;
        //
        //The 'opposite' is the y distance between a and b
        //const opposite:number= b.y - a.y;
        if ((b.y - a.y) !== 0) {
            opposite = b.y - a.y;
        }
        else {
            opposite = 0.1;
        }
        let adjacent;
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
        const thita = Math.atan(tanthita);
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
        return { start, mid, end };
    }
    // 
    //Returns the coordinates of the point which is 'hypo' units from 'a' along
    //the hypotenuse of a and b (which is inclined at angle thita) 
    get_point(a, thita, hypo) {
        //
        //The 'opp' is the 'hypo' times the sine of 'thita';
        const opp = hypo * Math.sin(thita);
        //
        //The 'adj' is the 'hypo' times the cosine of thita where thita is the
        //angle between 'adj' and 'hypo'
        const adj = hypo * Math.cos(thita);
        //
        //The x coordinate of the mid point is 'adj' units from the center of a
        const x = a.position.x + adj;
        //
        //The y coordinate of the mid point is the 'opp' units from the center of a
        const y = a.position.y + opp;
        //
        //The desired point is at x and and y units from the origin
        return new DOMPoint(x, y);
    }
}
