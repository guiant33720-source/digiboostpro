<?php
require_once '../config.php';
requireLogin('client');
header('Location: dashboard.php');
exit;
?>