<?php

declare(strict_types=1);

namespace Framework;

use Exception;

class Test
{
    private static array $_tests = [];

    public static function add($callback, $title = 'Test bez nazwy', $set = 'General'): void
    {
        self::$_tests[] = [
            'set'      => $set,
            'title'    => $title,
            'callback' => $callback
        ];
    }

    public static function run($before = null, $after = null): array
    {
        if ($before) {
            $before(self::$_tests);
        }

        $passed = [];
        $failed = [];
        $exceptions = [];

        foreach (self::$_tests as $test) {
            try {
                $result = call_user_func($test['callback']);
                if ($result) {

                    $passed[] = [
                        'set'   => $test['set'],
                        'title' => $test['title']
                    ];
                } else {
                    $failed[] = [
                        'set'   => $test['set'],
                        'title' => $test['title']
                    ];
                }
            } catch (Exception $e) {
                $exceptions[] = [
                    'set'   => $test['set'],
                    'title' => $test['title'],
                    'type'  => get_class($e)
                ];
            }
        }

        if ($after) {
            $after(self::$_tests);
        }

        return array(
            'passed'     => $passed,
            'failed'     => $failed,
            'exceptions' => $exceptions
        );
    }
}
