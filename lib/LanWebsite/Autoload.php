<?php

    function __autoload($name) {
    
        $file = 'lib/' . str_replace('_', '/', $name) . '.php';
        if (!file_exists($file)) throw new Exception("Class not found: '" . $name . '"');
        require_once $file;
    
    }
    
?>