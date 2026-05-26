<?php

declare(strict_types=1);

namespace PhpArrayJson\Tests;

use PhpArrayJson\ArrayLiteralParser;
use PhpArrayJson\ConversionError;
use PHPUnit\Framework\TestCase;

final class ArrayLiteralParserTest extends TestCase
{
    public function testParsesAssociativeArrayLiteral(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    'name' => 'shimabox',
    'age' => 40,
    'active' => true,
    'skills' => ['PHP', 'Go'],
]
PHP);

        self::assertSame([
            'name' => 'shimabox',
            'age' => 40,
            'active' => true,
            'skills' => ['PHP', 'Go'],
        ], $actual);
    }

    public function testParsesArrayLiteralWithOpenTagAndSemicolon(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
<?php

[
    'enabled' => false,
    'limit' => null,
];
PHP);

        self::assertSame([
            'enabled' => false,
            'limit' => null,
        ], $actual);
    }

    public function testParsesFloatIntegerKeyAndNestedAssociativeArray(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    10 => 'ten',
    'ratio' => 0.75,
    'profile' => [
        'location' => 'Tokyo',
    ],
]
PHP);

        self::assertSame([
            10 => 'ten',
            'ratio' => 0.75,
            'profile' => [
                'location' => 'Tokyo',
            ],
        ], $actual);
    }

    public function testRejectsFunctionCall(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['name' => getenv('USER')]");
    }

    public function testRejectsVariable(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['name' => \$name]");
    }

    public function testRejectsConstant(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['name' => SOME_CONSTANT]");
    }

    public function testRejectsArrayFunctionSyntax(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("array('name' => 'shimabox')");
    }

    public function testRejectsExtraTokenAfterRootArray(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('unexpected token');

        $parser->parse("['name' => 'shimabox']; ['extra']");
    }

    public function testRejectsDoubleQuotedString(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('double-quoted strings are not supported yet');

        $parser->parse('["name" => "shimabox"]');
    }

    public function testRejectsClassConstant(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['status' => SomeClass::VALUE]");
    }

    public function testRejectsStringConcatenation(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['name' => 'shima' . 'box']");
    }
}
