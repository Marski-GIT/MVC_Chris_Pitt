<?php declare(strict_types=1);

require_once '../Autoloader.php';

spl_autoload_register(['Autoloader', 'autoload']);


use Framework\Configuration;
use Framework\Test;

Test::add(
    function () {
        $configuration = new Configuration();
        return ($configuration instanceof Framework\Configuration);
    },
    'Tworzenie egzemplarza konfiguracji w niezainicjowanym stanie',
    'Configuration'
);

Test::add(
    function () {
        $configuration = new Configuration([
            'type' => 'ini'
        ]);

        $configuration = $configuration->initialize();
        return ($configuration instanceof Framework\Configuration\Driver\Ini);
    },
    'Inicjacja Configuration\Driver\ini',
    'Configuration\Driver\ini'
);

Test::add(
    function () {
        $configuration = new Configuration([
            'type' => 'ini'
        ]);

        $configuration = $configuration->initialize();
        $parsed = $configuration->parse('_configuration');

        return ($parsed->config->first == 'hello' && $parsed->config->second->second == 'bar');
    },
    'Configuration\Driver\ini przetwarza pliki konfiguracyjne',
    'Configuration\Driver\ini'
);

echo '<pre>';
print_r(Test::run());
echo '</pre>';
