<?php

function fqcnToPath(string $fqcn) {
    return str_replace('\\', '/', $fqcn) . '.php';
}

spl_autoload_register(function (string $class) {
    $path = fqcnToPath($class);

    require __DIR__ . '/' . $path;
});