<?php
session_start();
if (isset($_POST['langue'])) {
    $_SESSION['langue'] = $_POST['langue'];
}
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
