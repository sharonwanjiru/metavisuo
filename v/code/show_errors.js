import { metavisuo } from "./metavisuo.js";
import { dialog } from "./../../../mashamba/v/code/dialog.js";
//
// A class for reporting entity errors in metavisio class
export class metavisuo_ext extends metavisuo {
    //
    //Get the selected dbase from local storage
    selected_dbase;
    //
    //Use the library dialogbox strategy to display the errors that are in the
    //current selected entity
    async show_errors() {
        //
        //Get the error report
        const Report = await this.get_report();
        //
        //Use the report to create a dialog box
        const dlg = new report_dialog(Report);
        //
        //Use the dialog to show the report
        await dlg.administer();
    }
    //Get the error report from the currenty selected entity in this
    //metavisuo appliction
    async get_report() {
        //
        // Get the selected entity
        const entity = this.get_selected_entity();
        //
        // Get the entity errors
        const errors = entity.errors;
        //
        //Convert the errors to a string
        const report = this.convert_2_str(errors);
        //
        // Return the report of errors
        return report;
    }
    //
    // Get the selected entity
    get_selected_entity() {
        //
        // Get the current database that is selected
        const database = this.current_db;
        //
        // Get the entitities class and id #attribute
        const entities = database.entities;
        //
        //From the entiies get the one with class selected
        // Loop through each of the entities
        for (const selected in entities) {
            //
            // Get the current entity
            const entity = entities[selected];
            //
            // Check if the entity has the "selected" class
            if (entity.proxy.classList.contains("selected")) {
                return entity;
            }
        }
        //
        // If no entity with the "selected" class is found, return undefined
        return undefined;
    }
    //
    //Convert the errors to a string
    convert_2_str(errors) {
        //
        // If there is no error return
        if (errors.length === 0)
            return "No errors found.";
        //
        // Use the map function to extract the error messages
        return errors.map(error => `
        <details>
            <summary>${error.message}</summary>
            ${error.stack}
        </details>
    `)
            .join("<br/>");
    }
}
class report_dialog extends dialog {
    //
    //Define a class constructor
    constructor(report) {
        super({ url: "./show_errors.html", anchor: document.body }, report);
    }
    //
    // Saving data to a database
    async save(input) {
        return "ok";
    }
    //
    // Reads and returns data from a dialog
    async read() {
        //
        throw new Error("Read not expected");
    }
    //Override the populate dialog option
    populate(data) {
        //
        // Get the error reporting element 
        const element = this.get_element("my_error_report");
        //
        // Insert the report to the element
        element.innerHTML = data;
    }
}
