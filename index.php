<?php declare(strict_types=1);

require_once 'Autoloader.php';

spl_autoload_register(['Autoloader', 'autoload']);

use Framework\Configuration;
use Framework\Exceptions\ArgumentException;
use Framework\Exceptions\SyntaxException;

$configuration = new Configuration(['type' => 'ini']);

try {
    
    $configuration = $configuration->initialize();
    $conf = $configuration->parse('configuration');

} catch (ArgumentException|SyntaxException $e) {
    echo $e->getMessage();
}