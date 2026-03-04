<?php
session_start();
session_destroy();
// JS will clear localStorage session on the login page
header('Location: login.php?logged_out=1');
exit;
