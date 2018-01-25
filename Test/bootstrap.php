<?php

function path(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

require_once __DIR__ . path('/../vendor/autoload.php');
