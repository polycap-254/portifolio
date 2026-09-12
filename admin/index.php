<?php
require_once __DIR__ . '/../includes/auth.php';
redirect(url(isLoggedIn() ? '/admin/dashboard.php' : '/admin/login.php'));
