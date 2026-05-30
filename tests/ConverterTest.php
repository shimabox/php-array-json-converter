<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\Converter;
use PHPUnit\Framework\TestCase;

final class ConverterTest extends TestCase
{
    /**
     * PHP配列リテラル文字列をJSONへ変換する正常系を確認します。
     * parserとJSON formatterを通したユースケース全体の最小ケースです。
     */
    public function testConvertsArrayLiteralToJson(): void
    {
        $converter = new Converter();

        $result = $converter->arrayToJson("['name' => 'shimabox']");

        self::assertTrue($result->succeeded());
        self::assertSame("['name' => 'shimabox']", $result->phpArray);
        self::assertSame("{\n    \"name\": \"shimabox\"\n}", $result->json);
        self::assertNull($result->error);
    }

    /**
     * PHP配列リテラルとして未対応の構文がある場合に失敗結果を返すことを確認します。
     * 変換失敗時に元入力を保持し、JSON出力を空にする契約もカバーします。
     */
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

    /**
     * JSON文字列をPHP配列リテラルへ変換する正常系を確認します。
     */
    public function testConvertsJsonToArrayLiteral(): void
    {
        $converter = new Converter();

        $result = $converter->jsonToArray('{"name":"shimabox"}');

        self::assertTrue($result->succeeded());
        self::assertSame("[\n    'name' => 'shimabox',\n]", $result->phpArray);
        self::assertSame('{"name":"shimabox"}', $result->json);
        self::assertNull($result->error);
    }

    /**
     * JSONとして壊れている入力が失敗結果になることを確認します。
     */
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

    /**
     * top-level scalar JSONをPHP配列リテラルへ変換しないことを確認します。
     * `true` などを許可すると、Array to JSONへ戻せずround-tripが非対称になるため拒否します。
     */
    public function testRejectsTopLevelScalarJson(): void
    {
        $converter = new Converter();

        $result = $converter->jsonToArray('true');

        self::assertFalse($result->succeeded());
        self::assertSame('', $result->phpArray);
        self::assertSame('true', $result->json);
        self::assertSame('Top-level JSON value must be an object or array.', $result->error);
    }

    /**
     * JSON decodeの深さ上限を超える入力が失敗結果になることを確認します。
     * 過度なネストによる処理時間・メモリ使用量の増加を防ぐための境界テストです。
     */
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

    /**
     * Array to JSON / JSON to Arrayの両方向で `1.0` の型表現が崩れないことを確認します。
     */
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
