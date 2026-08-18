<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'protected_to_private' => false,
    ])
    ->setFinder($finder);
