<?php

$user = isset($_ENV["PIPELINE_AUTH_USER"]) ? $_ENV["PIPELINE_AUTH_USER"] : false;
$pass = isset($_ENV["PIPELINE_AUTH_PASS"]) ? $_ENV["PIPELINE_AUTH_PASS"] : false;

if (
    (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) ||
    ($_SERVER['PHP_AUTH_USER']!=$user || $_SERVER['PHP_AUTH_PW']!=$pass)
)
{
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'unauthorized';
    exit;
}

// $_SESSION["user"] = $_SERVER['PHP_AUTH_USER'];