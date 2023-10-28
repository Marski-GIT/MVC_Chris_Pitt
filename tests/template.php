<?php declare(strict_types=1);

require_once '../Autoloader.php';

spl_autoload_register(['Autoloader', 'autoload']);

use Framework\Template;
use Framework\Test;


$template = new Template([
    'implementation' => new Framework\Template\Implementation\Standard()
]);

Test::add(
    function () use ($template) {
        return ($template instanceof Framework\Template);
    },
    'Egzemplarz szablonu tworzy się',
    'Template'
);

Test::add(
    function () use ($template) {
        $template->parse("{echo 'hello world'}");
        $processed = $template->process();

        return ($processed == 'hello world');
    },
    'Szablon przetwarza znacznik echo',
    'Template'
);

Test::add(
    function () use ($template) {
        $template->parse("{script \$_text[] = 'foo bar' }");
        $processed = $template->process();

        return ($processed == 'foo bar');
    },
    'Szablon przetwarza znacznik script',
    'Template'
);

Test::add(
    function () use ($template) {
        $template->parse("
            {foreach \$number in \$numbers}{echo \$number_i},{echo \$number},{/foreach}"
        );
        $processed = $template->process([
            'numbers' => [1, 2, 3]
        ]);

        return (trim($processed) == '0,1,1,2,2,3,');
    },
    'Szablon przetwarza znacznik foreach',
    'Template'
);

Test::add(
    function () use ($template) {
        $template->parse("
            {for \$number in \$numbers}{echo \$number_i},{echo \$number},{/for}
        ");
        $processed = $template->process([
            'numbers' => [1, 2, 3]
        ]);

        return (trim($processed) == '0,1,1,2,2,3,');
    },
    'Szablon przetwarza znacznik for',
    'Template'
);

Test::add(
    function () use ($template) {
        $template->parse("
            {if \$check == \"yes\"}yes{/if}
            {elseif \$check == \"maybe\"}yes{/elseif}
            {else}yes{/else}
        ");

        $yes = $template->process([
            'check' => 'yes'
        ]);

        $maybe = $template->process([
            'check' => 'maybe'
        ]);

        $no = $template->process([
            'check' => null
        ]);

        return ($yes == $maybe && $maybe == $no);
    },
    'Szablon przetwarza znaczniki if, else oraz elseif',
    'Template'
);

Test::add(
    function () use ($template) {
        $template->parse("
            {macro foo(\$number)}
                {echo \$number + 2}
            {/macro}
            
            {echo foo(2)}
        ");
        $processed = $template->process();

        return ($processed == 4);
    },
    'Szablon przetwarza znacznik macro',
    'Template'
);

Test::add(
    function () use ($template) {
        $template->parse("
            {literal}
                {echo \"hello world\"}
            {/literal}
        ");
        $processed = $template->process();

        return (trim($processed) == "{echo \"hello world\"}");
    },
    'Szablon przetwarza znacznik literal',
    'Template'
);

echo '<pre>';
print_r(Test::run());
echo '</pre>';
