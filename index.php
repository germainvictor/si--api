<?php

// Grabs the URI and breaks it apart in case we have querystring stuff
$request_uri = explode('?', $_SERVER['REQUEST_URI'], 2);

// Route it up!
switch ($request_uri[0]) {
    // Home page
    case '/':
        require 'views/pages/portail.php';
        break;

    case '/home':
        require 'views/pages/home.php';
        break;

    case '/map':
        require 'views/pages/map.php';
        break;

    // Everything else
    default:
        header('HTTP/1.0 404 Not Found');
        require 'views/pages/404.php';
        break;
}