
class h{
    
   static async myfetch(){
        //
        //
        const formdata = new FormData();
        const name = "kamau";
        formdata.append('name', name);


        const init ={
            method : "post",
            body: "formdata"
        };

        const t= await fetch('save.php', init);
       
        const d= await t.text();
        
        alert(d);
    }
}

