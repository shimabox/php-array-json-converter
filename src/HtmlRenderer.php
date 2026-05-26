<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final class HtmlRenderer
{
    public function render(string $phpArray = '', string $json = '', ?string $error = null, ?string $focusTarget = null): string
    {
        $template = $this->loadTemplate();

        return strtr($template, [
            '{{ php_array }}' => $this->escape($phpArray),
            '{{ json }}' => $this->escape($json),
            '{{ error }}' => $this->escape($error ?? ''),
            '{{ focus_target }}' => $this->escape($focusTarget ?? ''),
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

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
