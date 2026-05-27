const phpArrayInput = document.getElementById('php-array-input');
const jsonInput = document.getElementById('json-input');
const errorPanel = document.querySelector('.error');
const errorMessage = document.getElementById('error-message');

function setError(message) {
    errorMessage.textContent = message;
    errorPanel.hidden = message === '';
}

async function convert(payload) {
    const response = await fetch('/api/convert', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });

    return response.json();
}

function applyConversionResult(result) {
    phpArrayInput.value = result.php_array ?? phpArrayInput.value;
    jsonInput.value = result.json ?? jsonInput.value;
    setError(result.error ?? '');
}

function focusStart(target) {
    target.focus();
    target.setSelectionRange(0, 0);
    target.scrollTop = 0;
}

async function runConversion(payload, focusTarget) {
    try {
        const result = await convert(payload);

        applyConversionResult(result);

        if (!result.error) {
            focusStart(focusTarget);
        }
    } catch (error) {
        setError('Conversion request failed.');
    }
}

document.getElementById('array-to-json').addEventListener('click', () => {
    runConversion({
        mode: 'array_to_json',
        php_array: phpArrayInput.value,
    }, jsonInput);
});

document.getElementById('json-to-array').addEventListener('click', () => {
    runConversion({
        mode: 'json_to_array',
        json: jsonInput.value,
    }, phpArrayInput);
});

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
