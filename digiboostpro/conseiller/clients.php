<?php
require_once '../config.php';
requireLogin('conseiller');
header('Location: dashboard.php');
exit;
?>