<?php declare(strict_types=1);

require_once '../Autoloader.php';

spl_autoload_register(['Autoloader', 'autoload']);

use Framework\Database;
use Framework\Exceptions\ArgumentException;
use Framework\Exceptions\ServiceException;
use Framework\Model;
use Framework\Registry;
use Framework\Test;

$database = new Database(array(
    'type'    => 'mysql',
    'options' => [
        'host'     => 'localhost',
        'username' => 'courses',
        'password' => 'courses',
        'schema'   => 'course_mvc_pitt'
    ]
));

try {

    $database = $database->initialize();
    $database = $database->connect();

} catch (ArgumentException $e) {
    echo 'ArgumentException: ' . $e->getMessage();
} catch (ServiceException $e) {
    echo 'ServiceException:' . $e->getMessage();
}


Registry::set('database', $database);


class Example extends Model
{
    /**
     * @readwrite
     * @column
     * @type autonumber
     * @primary
     */
    protected $_id;

    /**
     * @readwrite
     * @column
     * @type text
     * @length 32
     */
    protected $_name;

    /**
     * @readwrite
     * @column
     * @type datetime
     */
    protected $_created;
}

Test::add(
    function () use ($database) {
        $example = new Example();

        return ($database->sync($example) instanceof Framework\Database\Connector\Mysql);
    },
    'Model synchronizuje się',
    'Model'
);

Test::add(
    function () use ($database) {
        $example = new Example([
            'name'    => 'foo',
            'created' => date('Y-m-d H:i:s')
        ]);


//        echo '<pre>';
//        print_r($example);
//        echo '</pre>';


        return ($example->save() > 0);
    },
    'Model wstawia wiersze',
    'Model'
);

Test::add(
    function () use ($database) {
        return (Example::count() == 1);
    },
    'Model pobiera liczbę wierszy',
    'Model'
);

Test::add(
    function () use ($database) {
        $example = new Example(array(
            'name'    => 'foo',
            'created' => date('Y-m-d H:i:s')
        ));

        $example->save();
        $example->save();
        $example->save();

        return (Example::count() == 2);
    },
    'Model zapisuje jeden wiersz kilka razy',
    'Model'
);

Test::add(
    function () use ($database) {
        $example = new Example(array(
            'id'      => 1,
            'name'    => 'hello',
            'created' => date('Y-m-d H:i:s')
        ));
        $example->save();

        return (Example::first()->name == 'hello');
    },
    'Model zmienia wiersze',
    'Model'
);

Test::add(
    function () use ($database) {
        $example = new Example(array(
            'id' => 2
        ));
        $example->delete();

        return (Example::count() == 1);
    },
    'Model usuwa wiersze',
    'Model'
);


echo '<pre>';
print_r(Test::run());
echo '</pre>';
