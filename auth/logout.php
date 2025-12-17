<?php
session_start();
session_destroy();

header("Location: /CASE-STUDY/auth/login.php");
exit;
