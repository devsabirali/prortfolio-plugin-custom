<?php
// Minimal autoload for production build - PowerFolio Plugin

// Load Freemius SDK if available
if (file_exists(__DIR__ . '/freemius/wordpress-sdk/start.php')) {
    require_once __DIR__ . '/freemius/wordpress-sdk/start.php';
} elseif (file_exists(__DIR__ . '/freemius/start.php')) {
    require_once __DIR__ . '/freemius/start.php';
}

// Return a simple autoload function for compatibility
return function($class) {
    // Basic autoload functionality for production
    return false;
};
