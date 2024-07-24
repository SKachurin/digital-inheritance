<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
   // ->exclude('tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PhpCsFixer:risky' => true,
        '@PhpCsFixer' => true,
        '@DoctrineAnnotation' => true,
        '@PSR12:risky' => true,
        '@PSR12' => true,
        '@PSR2' => true,
        '@PSR1' => true,
        '@PHP80Migration:risky' => true,
        '@PHP84Migration' => true,
        'concat_space' => ['spacing' => 'one',],
        'mb_str_functions' => true,
        'strict_comparison' => false,
        'php_unit_data_provider_static' => false
    ])
    ->setFinder($finder)
;
