<?php

declare(strict_types=1);

use PhpArrayJson\ArrayLiteralFormatter;

$runner->test('formats associative array as short array literal', function (): void {
    $formatter = new ArrayLiteralFormatter();

    $actual = $formatter->format([
        'name' => 'shimabox',
        'age' => 40,
        'active' => true,
        'skills' => ['PHP', 'Go'],
    ]);

    assertSameValue(
        <<<'PHP'
[
    'name' => 'shimabox',
    'age' => 40,
    'active' => true,
    'skills' => [
        'PHP',
        'Go',
    ],
]
PHP,
        $actual
    );
});

$runner->test('formats empty array on one line', function (): void {
    $formatter = new ArrayLiteralFormatter();

    assertSameValue('[]', $formatter->format([]));
});

$runner->test('escapes string keys and values', function (): void {
    $formatter = new ArrayLiteralFormatter();

    $actual = $formatter->format([
        "owner's path" => "C:\\tmp\\owner's-file",
    ]);

    assertSameValue(
        <<<'PHP'
[
    'owner\'s path' => 'C:\\tmp\\owner\'s-file',
]
PHP,
        $actual
    );
});
