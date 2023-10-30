<html>
    <head>
        <title>login</title>
        <style>
            h2{
                color: blue;
                text-decoration: green;

            }
            textarea{
                background-color: bisque;
                color:green;

            }           
            label{
                display:block;
                color: blue;
                margin-top: 2%;

            }
            span:after{
                content: ":"; 
            }

            span{
                font-size:30px;
                font-family:serif;
                font:larger;

            }
            input{
                background-color: bisque;
                color:green;
            }

            body{
                text-align: center;
                /*padding-top: 30%;*/
            }

            form{
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
            }
            #bg-image{
                background:url("Mutall1.JPG"); 
                opacity: 0.2;
                height: 100vh;
            }
            button{
                background-color: #4CAF50; /* Green */
                border: none;
                color: red;
                padding: 15px 32px;
                display: inline-block;
                font-size: 16px;
                border-radius: 50px;
              }   
        </style>
        <script>
            const comment ={};
            function build_comment(){
                //
                //Add the title
                comment["title"]=document.getElementById('title').value;
                comment["cx"]=document.getElementById('cx').value;
                comment["cy"]=document.getElementById('cy').value;
                if (document.getElementById('adm').checked){
                    //
                    comment["administration"]=true;
                }
                if (document.getElementById('report').checked){
                    //
                    comment["reporting"]=true;
                }
                if (document.getElementById('visible').checked){
                    //
                    comment["visible"]=true;
                }
            }
        </script>
    </head>
    
    <body>
    </body>
</html>