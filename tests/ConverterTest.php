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

    public function testRejectsTopLevelScalarJson(): void
    {
        $converter = new Converter();

        $result = $converter->jsonToArray('true');

        self::assertFalse($result->succeeded());
        self::assertSame('', $result->phpArray);
        self::assertSame('true', $result->json);
        self::assertSame('Top-level JSON value must be an object or array.', $result->error);
    }

    public function testRejectsJsonWhenDepthLimitIsExceeded(): void
    {
        $converter = new Converter();
        $json = str_repeat('[', 129) . '0' . str_repeat(']', 129);

        $result = $converter->jsonToArray($json);

        self::assertFalse($result->succeeded());
        self::assertSame('', $result->phpArray);
        self::assertSame($json, $result->json);
        self::assertIsString($result->error);
        self::assertStringContainsString('JSON parse error:', $result->error);
    }

    public function testRoundTripPreservesFloatZeroFraction(): void
    {
        $converter = new Converter();

        $arrayResult = $converter->arrayToJson('[1.0]');
        $jsonResult = $converter->jsonToArray('[1.0]');

        self::assertTrue($arrayResult->succeeded());
        self::assertSame("[\n    1.0\n]", $arrayResult->json);

        self::assertTrue($jsonResult->succeeded());
        self::assertSame("[\n    1.0,\n]", $jsonResult->phpArray);
    }
}
