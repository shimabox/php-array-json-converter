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
            : '<section class="error" role="alert"><h2>Error</h2><p>' . $this->escape($error) . '</p></section>';

        return <<<HTML
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Array JSON Converter</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f1ea;
            --panel: #fffdf8;
            --ink: #17130f;
            --muted: #73685c;
            --line: #d9d0c3;
            --accent: #0f5f6b;
            --accent-ink: #ffffff;
            --error-bg: #fff1ed;
            --error-line: #d55c35;
            --code-bg: #20201d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                linear-gradient(135deg, rgba(15, 95, 107, 0.08), transparent 34rem),
                var(--bg);
            color: var(--ink);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .app-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0;
        }

        .page-header {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--ink);
            padding-bottom: 16px;
        }

        h1,
        h2,
        p {
            margin: 0;
        }

        h1 {
            font-size: clamp(2rem, 5vw, 4.5rem);
            line-height: 0.95;
            max-width: 760px;
        }

        .tagline {
            max-width: 360px;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .converter-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .editor-panel {
            display: grid;
            gap: 12px;
            min-width: 0;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        h2 {
            font-size: 0.9rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        textarea {
            width: 100%;
            min-height: 440px;
            resize: vertical;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--code-bg);
            color: #f8f0df;
            padding: 16px;
            font: inherit;
            line-height: 1.55;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
        }

        .editor-body {
            position: relative;
        }

        .editor-body textarea {
            padding-top: 50px;
        }

        .copy-button {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
            border: 1px solid rgba(248, 240, 223, 0.24);
            background: rgba(248, 240, 223, 0.1);
            color: #f8f0df;
            font-size: 0.78rem;
            padding: 7px 10px;
            backdrop-filter: blur(8px);
        }

        .copy-button:hover,
        .copy-button:focus-visible {
            background: rgba(248, 240, 223, 0.18);
        }

        .copy-button.is-copied {
            border-color: rgba(114, 220, 167, 0.7);
            color: #b9f4d5;
        }

        textarea:focus {
            outline: 3px solid rgba(15, 95, 107, 0.28);
            border-color: var(--accent);
        }

        button {
            border: 0;
            border-radius: 8px;
            background: var(--accent);
            color: var(--accent-ink);
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 10px 14px;
        }

        button:hover,
        button:focus-visible {
            background: #0b4c56;
        }

        button:focus-visible {
            outline: 3px solid rgba(15, 95, 107, 0.34);
            outline-offset: 2px;
        }

        .error {
            margin-top: 16px;
            border: 1px solid var(--error-line);
            border-left-width: 6px;
            border-radius: 8px;
            background: var(--error-bg);
            padding: 14px 16px;
        }

        .error h2 {
            color: #9f3517;
            margin-bottom: 6px;
        }

        @media (max-width: 820px) {
            .app-shell {
                width: min(100% - 20px, 1180px);
                padding: 20px 0;
            }

            .page-header,
            .converter-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                display: grid;
                align-items: start;
            }

            textarea {
                min-height: 320px;
            }
        }
    </style>
</head>
<body>
    <main class="app-shell">
        <header class="page-header">
            <h1>PHP Array JSON Converter</h1>
            <p class="tagline">Paste a PHP array literal or JSON document. Conversion runs locally and never executes PHP input.</p>
        </header>

        <form method="post" action="/convert" class="converter-grid">
            <section class="editor-panel">
                <div class="panel-header">
                    <h2>PHP Array</h2>
                    <button type="submit" name="mode" value="php_to_json">Array &rarr; JSON</button>
                </div>
                <div class="editor-body">
                    <button type="button" class="copy-button" data-copy-target="php-array-input">Copy</button>
                    <textarea id="php-array-input" name="php_array" rows="20" cols="60" spellcheck="false">{$phpArray}</textarea>
                </div>
            </section>
            <section class="editor-panel">
                <div class="panel-header">
                    <h2>JSON</h2>
                    <button type="submit" name="mode" value="json_to_php">JSON &rarr; Array</button>
                </div>
                <div class="editor-body">
                    <button type="button" class="copy-button" data-copy-target="json-input">Copy</button>
                    <textarea id="json-input" name="json" rows="20" cols="60" spellcheck="false">{$json}</textarea>
                </div>
            </section>
        </form>
        {$errorHtml}
    </main>
    <script>
        document.querySelectorAll('[data-copy-target]').forEach((button) => {
            button.addEventListener('click', async () => {
                const target = document.getElementById(button.dataset.copyTarget);

                if (!(target instanceof HTMLTextAreaElement)) {
                    return;
                }

                const originalText = button.textContent;

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(target.value);
                    } else {
                        target.focus();
                        target.select();
                        document.execCommand('copy');
                    }

                    button.textContent = 'Copied';
                    button.classList.add('is-copied');
                    window.setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('is-copied');
                    }, 1400);
                } catch (error) {
                    button.textContent = 'Failed';
                    window.setTimeout(() => {
                        button.textContent = originalText;
                    }, 1400);
                }
            });
        });
    </script>
</body>
</html>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
