<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'PhpArrayJson\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

final class TestRunner
{
    /** @var list<array{name: string, test: callable(): void}> */
    private array $tests = [];

    public function test(string $name, callable $test): void
    {
        $this->tests[] = [
            'name' => $name,
            'test' => $test,
        ];
    }

    public function run(): int
    {
        $failed = 0;

        foreach ($this->tests as $case) {
            try {
                $case['test']();
                fwrite(STDOUT, '.');
            } catch (Throwable $error) {
                $failed++;
                fwrite(STDOUT, 'F');
                fwrite(STDERR, PHP_EOL . $case['name'] . PHP_EOL);
                fwrite(STDERR, $error::class . ': ' . $error->getMessage() . PHP_EOL);
            }
        }

        fwrite(STDOUT, PHP_EOL);

        if ($failed > 0) {
            fwrite(STDERR, sprintf('%d test(s) failed.' . PHP_EOL, $failed));

            return 1;
        }

        fwrite(STDOUT, sprintf('%d test(s) passed.' . PHP_EOL, count($this->tests)));

        return 0;
    }
}

function assertSameValue(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

$runner = new TestRunner();

require __DIR__ . '/ArrayLiteralFormatterTest.php';

exit($runner->run());
