<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/config', __DIR__ . '/tests'])
;

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PER-CS3x0' => true,
        'no_unused_imports' => true,
        'single_line_empty_body' => false,
    ])
    ->setFinder($finder)
;
