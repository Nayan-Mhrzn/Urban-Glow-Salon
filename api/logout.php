<?php
/**
 * Logout Handler - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/app/Config/config.php';

logoutUser();
redirect(SITE_URL . '/login.php');

