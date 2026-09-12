<?php
require_once __DIR__ . '/../includes/auth.php';
logout();
// Start a fresh session just to set a flash for the login page
session_start();
setFlash('info', 'You have been logged out.');
redirect(url('/admin/login.php'));
