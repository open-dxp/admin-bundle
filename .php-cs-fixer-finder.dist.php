<?php

return PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude([
        __DIR__ . '/tests/_output',
        __DIR__ . '/tests/Support/_generated',
    ]);