<?php declare(strict_types=1);

require_once '../Autoloader.php';

spl_autoload_register(['Autoloader', 'autoload']);

use Framework\Exceptions\ServiceException;
use Framework\Test;
use Framework\Cache;

Test::add(
    function () {
        $cache = new Cache();
        return ($cache instanceof Cache);
    },
    'Tworzenie egzemplarza klasy Cache w niezainicjowanym stanie',
    'Cache'
);

Test::add(
    function () {
        $cache = new Cache([
            'type' => 'memcache'
        ]);

        $cache = $cache->initialize();

        return ($cache instanceof Framework\Cache\Driver\Memcache);
    },
    'Cache\Driver\Memcached inicjuje się',
    'Cache\Driver\Memcached'
);

Test::add(
    function () {
        $cache = new Cache([
            'type' => 'memcache'
        ]);

        $cache = $cache->initialize();
        return ($cache->connect() instanceof Framework\Cache\Driver\Memcache);
    },
    'Cache\Driver\Memcached łączy się i zwraca self',
    'Cache\Driver\Memcached'
);

Test::add(
    function () {
        $cache = new Cache([
            'type' => 'memcache'
        ]);

        $cache = $cache->initialize();
        $cache = $cache->connect();
        $cache = $cache->disconnect();

        try {
            $cache->get('anything');
        } catch (ServiceException $e) {
            return ($cache instanceof Framework\Cache\Driver\Memcache);
        }

        return false;
    },
    'Cache\Driver\Memcached rozłącza się i zwraca self',
    'Cache\Driver\Memcached'
);

Test::add(
    function () {
        $cache = new Cache([
            'type' => 'memcache'
        ]);

        $cache = $cache->initialize();
        $cache = $cache->connect();

        return ($cache->set('foo', 'bar', 1) instanceof Framework\Cache\Driver\Memcache);
    },
    'Cache\Driver\Memcached ustawia wartości i zwraca siebie',
    'Cache\Driver\Memcached'
);

Test::add(
    function () {
        $cache = new Cache([
            'type' => 'memcache'
        ]);

        $cache = $cache->initialize();
        $cache = $cache->connect();

        return ($cache->get('foo') == 'bar');
    },
    'Cache\Driver\Memcached pobiera wartości',
    'Cache\Driver\Memcached'
);

Test::add(
    function () {
        $cache = new Cache([
            'type' => 'memcache'
        ]);

        $cache = $cache->initialize();
        $cache = $cache->connect();

        return ($cache->get('404', 'baz') == 'baz');
    },
    'Cache\Driver\Memcached zwraca domyślne wartości',
    'Cache\Driver\Memcached'
);

Test::add(
    function () {
        $cache = new Cache([
            'type' => 'memcache'
        ]);

        $cache = $cache->initialize();
        $cache = $cache->connect();

        // Usypiamy, aby unieważnić powyższy 1-sekundowy bufor klucz-wartość
        sleep(1);

        return ($cache->get('foo') == null);
    },
    'Cache\Driver\Memcached wygasza wartości',
    'Cache\Driver\Memcached'
);

Test::add(
    function () {
        $cache = new Cache([
            'type' => 'memcache'
        ]);

        $cache = $cache->initialize();
        $cache = $cache->connect();

        $cache = $cache->set('Witaj,', 'świecie');
        $cache = $cache->erase('Witaj,');

        return ($cache->get('Witaj,') == null && $cache instanceof Framework\Cache\Driver\Memcache);
    },
    'Cache\Driver\Memcached usuwa wartości i zwraca self',
    'Cache\Driver\Memcached'
);

echo '<pre>';
print_r(Test::run());
echo '</pre>';
