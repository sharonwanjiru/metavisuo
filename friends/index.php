<?php
// Pause duration for each image in seconds
$pause = 20;
$images = array_diff(scandir("./images/"), array('.', '..'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Karla" rel="stylesheet">
    <title>Tree Weeding Event</title>
    <style>
        body {
            padding: 0;
            margin: 0;
            background-color: floralwhite;
        }

        /* h1 {
            margin-left: 450px;
            font-size: 40px;
            font-family: "Karla", sans-serif;
            font-weight: bold;
        } */
        
        blink{
            margin-left: 200px;
            font-size: 40px;
            font-family: "Karla", sans-serif;
            font-weight: bold;
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
        0% {
            opacity: 1;
            color: antiquewhite;
        }

        50% {
            opacity: 3;
            color: aqua;
        }

        100% {
            opacity: 1;
            color: blue;
        }
        }

        .slider_frame {
            overflow: hidden;
            height: 600px;
            /* width: 400px; */
            width: 900px;
            margin-left: 300px;
            margin-top: 20px;
        }

        @keyframes slide_animation {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(calc(-100% * <?php echo count($images); ?>));
            }
        }

        .slide_images {
            display: flex;
            height: 100%;
            width: auto;
            animation: slide_animation <?php echo  count($images) * $pause; ?>s infinite linear;
            animation-timing-function: steps(1, end) steps(<?php echo count($images); ?>, end);
        }


        .img_container {
            height: 100%;
            position: relative;
            float: left;
        }

        img {
            height: 100%;
            width: 600px;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <!--
          Type setting greetings. -->
          <blink>Friends of Ngong Hills- Tree Weeding Event</blink>
    <!-- <h1>Friends of Ngong Hills- Tree Weeding Event</h1> -->
    <div class="slider_frame">
        <div class="slide_images">
            <?php

            foreach ($images as $image) {
                echo '<div class="img_container">';
                echo '<img src="images/' . $image . '" alt="Friends of Ngong Hills">';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>

</html>