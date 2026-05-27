<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\ArrayLiteralParser;
use PhpArrayJsonConverter\ConversionError;
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

    public function testParsesEmptyArrayLiteral(): void
    {
        $parser = new ArrayLiteralParser();

        self::assertSame([], $parser->parse('[]'));
    }

    public function testParsesEscapedSingleQuotedString(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    'quote' => 'It\'s ok',
    'slash' => 'C:\\temp',
]
PHP);

        self::assertSame([
            'quote' => "It's ok",
            'slash' => 'C:\temp',
        ], $actual);
    }

    public function testParsesImplicitIndexAfterExplicitIntegerKeyLikePhp(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    2 => 'two',
    'three',
    0 => 'zero',
    'four',
]
PHP);

        self::assertSame([
            2 => 'two',
            3 => 'three',
            0 => 'zero',
            4 => 'four',
        ], $actual);
    }

    public function testParsesNegativeNumbers(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    'int' => -123,
    'float' => -12.34,
    -1 => 'negative key',
]
PHP);

        self::assertSame([
            'int' => -123,
            'float' => -12.34,
            -1 => 'negative key',
        ], $actual);
    }

    public function testParsesDeeplyNestedArrayLiteral(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    'level1' => [
        'level2' => [
            'level3' => [
                'level4' => [
                    'value' => 'deep',
                ],
            ],
        ],
    ],
]
PHP);

        self::assertSame([
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'value' => 'deep',
                        ],
                    ],
                ],
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

    public function testRejectsFunctionCallWithoutExecutingInput(): void
    {
        $parser = new ArrayLiteralParser();
        $path = tempnam(sys_get_temp_dir(), 'array-parser-');
        self::assertIsString($path);
        unlink($path);

        try {
            $parser->parse("['written' => file_put_contents('{$path}', 'executed')]");
            self::fail('Expected ConversionError was not thrown.');
        } catch (ConversionError $error) {
            self::assertStringContainsString('Unsupported PHP syntax', $error->getMessage());
            self::assertFileDoesNotExist($path);
        }
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

    public function testRejectsSpreadOperator(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("[...'items']");
    }
}
