<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final class HtmlRenderer
{
    public function render(string $phpArray = '', string $json = '', ?string $error = null): string
    {
        $template = $this->loadTemplate();

        return strtr($template, [
            '{{ php_array }}' => $this->escape($phpArray),
            '{{ json }}' => $this->escape($json),
            '{{ error_html }}' => $this->renderError($error),
        ]);
    }

    private function loadTemplate(): string
    {
        $template = file_get_contents(dirname(__DIR__) . '/templates/index.html');

        if ($template === false) {
            throw new \RuntimeException('Failed to load HTML template.');
        }

        return $template;
    }

    private function renderError(?string $error): string
    {
        if ($error === null) {
            return '';
        }

        return '<section class="error" role="alert"><h2>Error</h2><p>' . $this->escape($error) . '</p></section>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
