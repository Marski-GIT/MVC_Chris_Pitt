<?php declare(strict_types=1);

require_once '../Autoloader.php';

spl_autoload_register(['Autoloader', 'autoload']);

use Framework\Database;
use Framework\Exceptions\ServiceException;
use Framework\Test;

$options = array(
    'type'    => 'mysql',
    'options' => [
        'host'     => 'localhost',
        'username' => 'courses',
        'password' => 'courses',
        'schema'   => 'course_mvc_pitt'
    ]
);

Test::add(
    function () {
        $database = new Database();
        return ($database instanceof Framework\Database);
    },
    'Tworzenie egzemplarza bazy danych w niezainicjowanym stanie',
    'Database'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();

        return ($database instanceof Framework\Database\Connector\Mysql);
    },
    'Inicjacja Database\Connector\Mysql',
    'Database\Connector\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        return ($database instanceof Framework\Database\Connector\Mysql);
    },
    'Database\Connector\Mysql łączy się i zwraca siebie',
    'Database\Connector\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        $database = $database->disconnect();

        try {
            $database->execute('SELECT 1');
        } catch (ServiceException $e) {
            return ($database instanceof Framework\Database\Connector\Mysql);
        }

        return false;
    },
    'Database\Connector\Mysql rozłącza się i zwraca siebie',
    'Database\Connector\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        return ($database->escape("foo'" . 'bar"') == "foo\\'bar\\\"");
    },
    'Database\Connector\Mysql zastępuje znaki specjalne symbolami',
    'Database\Connector\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();


        $database->execute('JAKIŚ NIEPOPRAWNY KOD SQL');

        var_dump($database->getLastError());

        return (bool)$database->getLastError();
    },
    'Database\Connector\Mysql zwraca ostatni błąd',
    'Database\Connector\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $database->execute("
            DROP TABLE IF EXISTS `tests`;
        ");
        $database->execute("
            CREATE TABLE `tests` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `number` int(11) NOT NULL,
                `text` varchar(255) NOT NULL,
                `boolean` tinyint(4) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");


        return !$database->getLastError();
    },
    'Database\Connector\Mysql wykonuje zapytania',
    'Database\Connector\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        for ($i = 0; $i < 4; $i++) {
            $database->execute("
                INSERT INTO `tests` (`number`, `text`, `boolean`) VALUES ('1337', 'text', '0');
            ");
        }

        return $database->lastInsertId;
    },
    'Database\Connector\Mysql zwraca ostatni wstawiony identyfikator',
    'Database\Connector\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $database->execute("
            UPDATE `tests` SET `number` = 1338;
        ");

        return $database->affectedRows;
    },
    'Database\Connector\Mysql zwraca liczbę zmienionych wierszy',
    'Database\Connector\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        $query = $database->query();

        return ($query instanceof Framework\Database\Query\Mysql);
    },
    'Database\Connector\Mysql zwraca egzemplarz klasy Database\Query\Mysql',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        $query = $database->query();

        return ($query->connector instanceof Framework\Database\Connector\Mysql);
    },
    'Database\Query\Mysql odnosi się do konektora',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $row = $database->query()
            ->from('tests')
            ->first();


        return ($row['id'] == 1);
    },
    'Database\Query\Mysql pobiera pierwszy wiersz',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $rows = $database->query()
            ->from('tests')
            ->all();

        return (sizeof($rows[0]) == 4);
    },
    'Database\Query\Mysql pobiera kilka wierszy',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $count = $database
            ->query()
            ->from('tests')
            ->count();

        return ($count == 4);
    },
    'Database\Query\Mysql pobiera liczbę wierszy',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $rows = $database->query()
            ->from('tests')
            ->limit(1, 2)
            ->order('id', 'desc')
            ->all();

        return (sizeof($rows) == 1 && $rows[0]['id'] == 3);
    },
    'Database\Query\Mysql obsługuje klauzule LIMIT, OFFSET, ORDER oraz DIRECTION',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $rows = $database->query()
            ->from('tests')
            ->where('id != ?', 1)
            ->where('id != ?', 3)
            ->where('id != ?', 4)
            ->all();

        return (sizeof($rows) == 1 && $rows[0]['id'] == 2);
    },
    'Database\Query\Mysql obsługuje klauzule WHERE',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $rows = $database->query()
            ->from('tests', [
                'id' => 'foo'
            ])
            ->all();

        return (sizeof($rows) && isset($rows[0]['foo']) && $rows[1]['foo'] == 2);
    },
    'Database\Query\Mysql tworzy aliasy pól',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $rows = $database->query()
            ->from('tests', [
                'tests.id' => 'foo'
            ])
            ->join('tests AS baz', 'tests.id = baz.id', [
                'baz.id' => 'bar'
            ])
            ->all();

        return (sizeof($rows) && $rows[0]['foo'] == $rows[0]['bar']);
    },
    'Database\Query\Mysql łączy tabele i tworzy aliasy złączonych pól',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $result = $database->query()
            ->from('tests')
            ->save([
                'number'  => 3,
                'text'    => 'foo',
                'boolean' => true
            ]);

        return ($result == 5);
    },
    'Database\Query\Mysql wstawia wiersze',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $result = $database->query()
            ->from('tests')
            ->where('id = ?', 5)
            ->save([
                'number'  => 3,
                'text'    => 'foo',
                'boolean' => false
            ]);

        return ($result == 0);
    },
    'Database\Query\Mysql modyfikuje wiersze',
    'Database\Query\Mysql'
);

Test::add(
    function () use ($options) {
        $database = new Database($options);
        $database = $database->initialize();
        $database = $database->connect();

        $database->query()
            ->from('tests')
            ->delete();

        return ($database->query()->from('tests')->count() == 0);
    },
    'Database\Query\Mysql usuwa wiersze',
    'Database\Query\Mysql'
);

echo '<pre>';
print_r(Test::run());
echo '</pre>';
