<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->notPath([
        'tests/bootstrap.php',
        'tests/object-manager.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;
