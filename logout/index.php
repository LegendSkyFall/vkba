<?php
# start session
session_start();
# destroy session
session_destroy();
# go to login page
header("Location: ../login");
exit();
?>
