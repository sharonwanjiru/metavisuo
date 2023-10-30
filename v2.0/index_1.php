 <?php

//Start the session
include 'library.php';

    $_SESSION['username'] = $_REQUEST['username'];
    $_SESSION['password'] = $_REQUEST['password'];


?>
<script>
    window.close();
</script>
