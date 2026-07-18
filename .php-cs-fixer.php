<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'php_unit_test_class_requires_covers' => false,
        'php_unit_internal_class' => false,
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ])
    ->setFinder($finder);
