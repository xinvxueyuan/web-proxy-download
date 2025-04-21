<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use Admin\Auth;

Auth::getInstance()->logout();
header('Location: /admin/login.php');
exit;