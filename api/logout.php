<?php
/**
 * Logout Handler - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/config/config.php';

logoutUser();
redirect(SITE_URL . '/login.php');

