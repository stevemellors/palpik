<?php
require_once __DIR__.'/../inc/session_boot.php';
if (!empty($_SESSION['admin_email'])) { header('Location: https://www.palletpiks.com/admin/panel.php'); exit; }
header('Location: https://www.palletpiks.com/admin/login.php'); exit;
