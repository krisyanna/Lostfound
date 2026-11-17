<?php require 'inc.php';
if($_SERVER['REQUEST_METHOD']==='POST'){chk();logit('logout');$_SESSION=[];session_destroy();}
header('Location: login.php');
