<?php

declare(strict_types=1);

namespace Framework;

use Exception;
use Framework\Exceptions\ImplementationException;
use Framework\Exceptions\ParserException;


if (!function_exists("create_function")) {
    function create_function($arg, $body)
    {
        static $cache = []; // A static array used to store previously created functions.
        static $max_cache_size = 64; // The maximum size of the cache.
        static $sorter; // A callback function used to sort the cache by hit count.

        if ($sorter === null) {
            // Define the sorter callback function.
            $sorter = function ($a, $b) {
                if ($a->hits == $b->hits) {
                    return 0;
                }
                return $a->hits < $b->hits ? 1 : -1;
            };
        }

        // Generate a unique key for the current function.
        $crc = crc32($arg . "\\x00" . $body);
        if (isset($cache[$crc])) {
            // If the function has already been created and cached, increment the hit count and return the cached function.
            ++$cache[$crc][1];
            return $cache[$crc][0];
        }

        if (sizeof($cache) >= $max_cache_size) {
            // If the cache size limit is reached, sort the cache by hit count and remove the least-used function.
            uasort($cache, $sorter);
            array_pop($cache);
        }

        // Create a new anonymous function using `eval` and store it in the cache along with a hit count of 0.
        $cache[$crc] = [
            ($cb = eval("return function(" . $arg . "){" . $body . "};")),
            0,
        ];
        return $cb;
    }
}

class Template extends Base
{
    /**
     * @readwrite
     */
    protected object $_implementation;

    /**
     * @readwrite
     */
    protected string $_header = "if (is_array(\$_data) && sizeof(\$_data)) extract(\$_data); \$_text = [];";

    /**
     * @readwrite
     */
    protected string $_footer = "return implode(\$_text);";

    /**
     * @read
     */
    protected string $_code;

    /**
     * @read
     */
    protected $_function;

    /**
     * @param string $name
     * @return ImplementationException
     */
    public function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda' . $name . ' nie jest zaimplementowana');
    }

    protected function _arguments($source, $expression): array
    {
        $args = $this->_array($expression, [
            $expression => [
                'opener' => '{',
                'closer' => '}'
            ]
        ]);

        $tags = $args['tags'];
        $arguments = [];
        $sanitized = StringMethods::sanitize($expression, '()[],.<>*$@');

        foreach ($tags as $i => $tag) {
            $sanitized = str_replace($tag, '(.*)', $sanitized);
            $tags[$i] = str_replace(['{', '}'], '', $tag);
        }

        if (preg_match("#{$sanitized}#", $source, $matches)) {
            foreach ($tags as $i => $tag) {
                $arguments[$tag] = $matches[$i + 1];
            }
        }

        return $arguments;
    }

    protected function _tag(string $source): array
    {
        $tag = null;
        $arguments = [];
        $closer = false;

        $match = $this->_implementation->match($source);
        if (empty($match)) {
            return [];
        }

        $delimiter = $match['delimiter'];
        $type = $match['type'];

        $start = strlen($type['opener']);
        $end = strpos($source, $type['closer']);
        $extract = substr($source, $start, $end - $start);

        if (isset($type['tags'])) {
            $tags = implode('|', array_keys($type['tags']));
            $regex = "#^(/){0,1}({$tags})\s*(.*)$#";

            if (!preg_match($regex, $extract, $matches)) {
                return [];
            }

            $tag = $matches[2];
            $extract = $matches[3];
            $closer = !!$matches[1];
        }

        if ($tag && $closer) {
            return [
                'tag'       => $tag,
                'delimiter' => $delimiter,
                'closer'    => true,
                'source'    => false,
                'arguments' => false,
                'isolated'  => $type['tags'][$tag]['isolated']
            ];
        }

        if (isset($type['arguments'])) {
            $arguments = $this->_arguments($extract, $type['arguments']);
        } else if ($tag && isset($type['tags'][$tag]['arguments'])) {
            $arguments = $this->_arguments($extract, $type['tags'][$tag]['arguments']);
        }

        return [
            'tag'       => $tag,
            'delimiter' => $delimiter,
            'closer'    => false,
            'source'    => $extract,
            'arguments' => $arguments,
            'isolated'  => (!empty($type['tags']) ? $type['tags'][$tag]['isolated'] : false)
        ];
    }

    protected function _array(string $source): array
    {
        $parts = [];
        $tags = [];
        $all = [];

        $type = null;
        $delimiter = null;

        while ($source) {
            $match = $this->_implementation->match($source);

            if (empty($match)) {
                break;
            }

            $type = $match['type'];
            $delimiter = $match['delimiter'];

            $opener = strpos($source, $type['opener']);
            $closer = strpos($source, $type['closer']) + strlen($type['closer']);

            if ($opener !== false) {
                $parts[] = substr($source, 0, $opener);
                $tags[] = substr($source, $opener, $closer - $opener);
                $source = substr($source, $closer);
            } else {
                $parts[] = $source;
                $source = '';
            }
        }

        foreach ($parts as $i => $part) {
            $all[] = $part;
            if (isset($tags[$i])) {
                $all[] = $tags[$i];
            }
        }

        return [
            'text' => ArrayMethods::clean($parts),
            'tags' => ArrayMethods::clean($tags),
            'all'  => ArrayMethods::clean($all)
        ];
    }

    protected function _tree(array $array)
    {
        $current = [
            'children' => []
        ];

        foreach ($array as $i => $node) {
            $result = $this->_tag($node);

            if (!empty($result)) {
                $tag = $result['tag'] ?? '';
                $arguments = $result['arguments'] ?? '';

                if ($tag) {
                    if (!$result['closer']) {
                        $last = ArrayMethods::last($current['children']);

                        if ($result['isolated'] && is_string($last)) {
                            array_pop($current['children']);
                        }

                        $current['children'][] = [
                            'index'     => $i,
                            'parent'    => &$current,
                            'children'  => [],
                            'raw'       => $result['source'],
                            'tag'       => $tag,
                            'arguments' => $arguments,
                            'delimiter' => $result['delimiter'],
                            'number'    => sizeof($current['children'])
                        ];
                        $current =& $current['children'][sizeof($current['children']) - 1];

                    } else if (isset($current['tag']) && $result['tag'] == $current['tag']) {
                        $start = $current['index'] + 1;
                        $length = $i - $start;
                        $current['source'] = implode(array_slice($array, $start, $length));
                        $current =& $current['parent'];
                    }
                } else {
                    $current['children'][] = [
                        'index'     => $i,
                        'parent'    => &$current,
                        'children'  => [],
                        'raw'       => $result['source'],
                        'tag'       => $tag,
                        'arguments' => $arguments,
                        'delimiter' => $result['delimiter'],
                        'number'    => sizeof($current['children'])
                    ];
                }
            } else {
                $current['children'][] = $node;
            }
        }

        return $current;
    }

    protected function _script($tree)
    {
        $content = [];

        if (is_string($tree)) {
            $tree = addslashes($tree);
            return "\$_text[] = \"{$tree}\";";
        }

        if (sizeof($tree['children']) > 0) {
            foreach ($tree['children'] as $child) {
                $content[] = $this->_script($child);
            }
        }

        if (isset($tree['parent'])) {
            return $this->_implementation->handle($tree, implode($content));
        }

        return implode($content);
    }

    /**
     * @throws ImplementationException
     */
    public function parse($template): Template
    {
        if (!is_a($this->_implementation, 'Framework\Template\Implementation')) {
            throw new ImplementationException('Błędna przestrzeń nazw dla parsera.');
        }

        $array = $this->_array($template);
        $tree = $this->_tree($array['all']);

        $this->_code = $this->_header . $this->_script($tree) . $this->_footer;
        $this->_function = create_function("\$_data", $this->_code);

        return $this;
    }

    /**
     * @throws ParserException
     */
    public function process($data = [])
    {
        if ($this->_function == null) {
            throw new ParserException('Brak funkcji.');
        }

        try {
            $function = $this->_function;
            return $function($data);
        } catch (Exception $e) {
            throw new ParserException('Błąd wywołania funkcji.');
        }
    }
}