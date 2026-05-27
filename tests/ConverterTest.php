<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\Converter;
use PHPUnit\Framework\TestCase;

final class ConverterTest extends TestCase
{
    public function testConvertsArrayLiteralToJson(): void
    {
        $converter = new Converter();

        $result = $converter->arrayToJson("['name' => 'shimabox']");

        self::assertTrue($result->succeeded());
        self::assertSame("['name' => 'shimabox']", $result->phpArray);
        self::assertSame("{\n    \"name\": \"shimabox\"\n}", $result->json);
        self::assertNull($result->error);
    }

    public function testReturnsArrayParseError(): void
    {
        $converter = new Converter();

        $result = $converter->arrayToJson("['name' => getenv('USER')]");

        self::assertFalse($result->succeeded());
        self::assertSame("['name' => getenv('USER')]", $result->phpArray);
        self::assertSame('', $result->json);
        self::assertIsString($result->error);
        self::assertStringContainsString('Unsupported PHP syntax', $result->error);
    }

    public function testConvertsJsonToArrayLiteral(): void
    {
        $converter = new Converter();

        $result = $converter->jsonToArray('{"name":"shimabox"}');

        self::assertTrue($result->succeeded());
        self::assertSame("[\n    'name' => 'shimabox',\n]", $result->phpArray);
        self::assertSame('{"name":"shimabox"}', $result->json);
        self::assertNull($result->error);
    }

    public function testReturnsJsonParseError(): void
    {
        $converter = new Converter();

        $result = $converter->jsonToArray('{"name":');

        self::assertFalse($result->succeeded());
        self::assertSame('', $result->phpArray);
        self::assertSame('{"name":', $result->json);
        self::assertIsString($result->error);
        self::assertStringContainsString('JSON parse error:', $result->error);
    }
}
