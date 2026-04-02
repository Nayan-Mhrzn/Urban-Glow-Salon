<?php
/**
 * Logout Handler - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/includes/config.php';

logoutUser();
redirect(SITE_URL . '/login.php');
