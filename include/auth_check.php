<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: /CASE-STUDY/auth/login.php");
  exit;
}
