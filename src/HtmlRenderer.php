<?php

declare(strict_types=1);

namespace PhpArrayJson;

final class HtmlRenderer
{
    public function render(string $phpArray = '', string $json = '', ?string $error = null): string
    {
        $phpArray = $this->escape($phpArray);
        $json = $this->escape($json);
        $errorHtml = $error === null
            ? ''
            : '<section class="error"><h2>Error</h2><p>' . $this->escape($error) . '</p></section>';

        return <<<HTML
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Array JSON Converter</title>
</head>
<body>
    <h1>PHP Array JSON Converter</h1>
    <form method="post" action="/convert">
        <section>
            <h2>PHP Array</h2>
            <textarea name="php_array" rows="20" cols="60">{$phpArray}</textarea>
            <button type="submit" name="mode" value="php_to_json">PHP to JSON</button>
        </section>
        <section>
            <h2>JSON</h2>
            <textarea name="json" rows="20" cols="60">{$json}</textarea>
            <button type="submit" name="mode" value="json_to_php">JSON to PHP</button>
        </section>
    </form>
    {$errorHtml}
</body>
</html>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
