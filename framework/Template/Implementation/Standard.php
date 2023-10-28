<?php

declare(strict_types=1);

namespace Framework\Template\Implementation;

use Framework\Template\Implementation;

class Standard extends Implementation
{
    protected array $_map = [
        'echo'      => [
            'opener'  => '{echo',
            'closer'  => '}',
            'handler' => '_echo'
        ],
        'script'    => [
            'opener'  => '{script',
            'closer'  => '}',
            'handler' => '_script'
        ],
        'statement' => [
            'opener' => '{',
            'closer' => '}',
            'tags'   => [
                'foreach' => [
                    'isolated'  => false,
                    'arguments' => '{element} in {object}',
                    'handler'   => '_each'
                ],
                'for'     => [
                    'isolated'  => false,
                    'arguments' => '{element} in {object}',
                    'handler'   => '_for'
                ],
                'if'      => [
                    'isolated'  => false,
                    'arguments' => null,
                    'handler'   => '_if'
                ],
                'elseif'  => [
                    'isolated'  => true,
                    'arguments' => null,
                    'handler'   => '_elif'
                ],
                'else'    => [
                    'isolated'  => true,
                    'arguments' => null,
                    'handler'   => '_else'
                ],
                'macro'   => [
                    'isolated'  => false,
                    'arguments' => '{name}({args})',
                    'handler'   => '_macro'
                ],
                'literal' => [
                    'isolated'  => false,
                    'arguments' => null,
                    'handler'   => '_literal'
                ]
            ]
        ]
    ];

    protected function _echo(array $tree, $content): string
    {
        $raw = $this->_script($tree, $content);
        return "\$_text[] = {$raw}";
    }

    protected function _script(array $tree, $content): string
    {
        $raw = !empty($tree['raw']) ? $tree['raw'] : '';
        return "{$raw};";
    }

    protected function _each(array $tree, $content): string
    {
        $object = $tree['arguments']['object'];
        $element = $tree['arguments']['element'];

        return $this->_loop(
            $tree,
            "foreach ({$object} as {$element}_i => {$element}) {
                    {$content}
                }"
        );
    }

    protected function _for(array $tree, $content): string
    {
        $object = $tree['arguments']['object'];
        $element = $tree['arguments']['element'];

        return $this->_loop(
            $tree,
            "for ({$element}_i = 0; {$element}_i < sizeof({$object}); {$element}_i++) {
                    {$element} = {$object}[{$element}_i];
                    {$content}
                }"
        );
    }

    protected function _if(array $tree, $content): string
    {
        $raw = $tree['raw'];
        return "if ({$raw}) {{$content}}";
    }

    protected function _elif(array $tree, $content): string
    {
        $raw = $tree['raw'];
        return "elseif ({$raw}) {{$content}}";
    }

    protected function _else($tree, $content): string
    {
        return "else {{$content}}";
    }

    protected function _macro(array $tree, $content): string
    {
        $arguments = $tree['arguments'];
        $name = $arguments['name'];
        $args = $arguments['args'];

        return "function {$name}({$args}) {
                \$_text = [];
                {$content}
                return implode(\$_text);
            }";
    }

    protected function _literal(array $tree, $content): string
    {
        $source = addslashes($tree['source']);
        return "\$_text[] = \"{$source}\";";
    }

    protected function _loop(array $tree, $inner): string
    {
        $number = $tree['number'];
        $object = $tree['arguments']['object'];
        $children = $tree['parent']['children'];

        if (!empty($children[$number + 1]['tag']) && $children[$number + 1]['tag'] == 'else') {
            return "if (is_array({$object}) && sizeof({$object}) > 0) {{$inner}}";
        }
        return $inner;
    }
}
