/**
 * Norwegian Amount Input Mask
 * Formats numbers with space as thousand separator
 * No decimals shown (integers only)
 */

function formatNorwegianAmount(value) {
    if (!value) return '';

    // Remove all non-numeric characters except minus
    let numericValue = value.toString().replace(/[^\d-]/g, '');

    // Handle negative numbers
    const isNegative = numericValue.startsWith('-');
    if (isNegative) {
        numericValue = numericValue.substring(1);
    }

    // Convert to integer (no decimals)
    const intValue = parseInt(numericValue) || 0;

    // Format with space as thousand separator
    let formatted = intValue.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

    // Add negative sign back if needed
    if (isNegative && intValue > 0) {
        formatted = '-' + formatted;
    }

    return formatted;
}

function parseNorwegianAmount(formattedValue) {
    if (!formattedValue) return null;

    // Remove spaces and convert to number
    const numericValue = formattedValue.toString().replace(/\s/g, '');
    const parsed = parseInt(numericValue);

    return isNaN(parsed) ? null : parsed;
}

function applyNorwegianAmountMask(input) {
    if (!input) return;

    // Store the original value for form submission
    let originalValue = input.value;

    // Format the display value
    input.value = formatNorwegianAmount(originalValue);

    // Handle input events
    input.addEventListener('input', function(e) {
        const cursorPosition = e.target.selectionStart;
        const oldValue = e.target.value;
        const newValue = formatNorwegianAmount(e.target.value);

        e.target.value = newValue;

        // Try to maintain cursor position
        const newCursorPosition = cursorPosition + (newValue.length - oldValue.length);
        e.target.setSelectionRange(newCursorPosition, newCursorPosition);
    });

    // Handle focus events - show formatted value
    input.addEventListener('focus', function(e) {
        // Keep formatted value when focused
        e.target.value = formatNorwegianAmount(e.target.value);
    });

    // Handle blur events - ensure proper formatting and store raw value
    input.addEventListener('blur', function(e) {
        const rawValue = parseNorwegianAmount(e.target.value);

        // Store raw value in a hidden attribute for form submission
        if (rawValue !== null) {
            e.target.setAttribute('data-raw-value', rawValue);
        }

        // Keep formatted display
        e.target.value = formatNorwegianAmount(e.target.value);
    });

    // Handle form submission - ensure raw values are submitted
    const form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            const rawValue = parseNorwegianAmount(input.value);
            if (rawValue !== null) {
                input.value = rawValue;
            }
        });
    }
}

// Auto-apply to amount input fields when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Target TextInputColumn fields for amounts
    const amountFields = document.querySelectorAll(
        'input[wire\\:model*="income_amount"],' +
        'input[wire\\:model*="expence_amount"],' +
        'input[wire\\:model*="asset_market_amount"],' +
        'input[wire\\:model*="mortgage_amount"],' +
        'input[wire\\:model*="asset_acquisition_amount"],' +
        'input[wire\\:model*="asset_equity_amount"],' +
        'input[wire\\:model*="asset_taxable_initial_amount"],' +
        'input[wire\\:model*="asset_paid_amount"],' +
        'input[wire\\:model*="mortgage_gebyr"]'
    );

    amountFields.forEach(applyNorwegianAmountMask);
});

// Re-apply masks when Livewire updates the DOM
document.addEventListener('livewire:navigated', function() {
    const amountFields = document.querySelectorAll(
        'input[wire\\:model*="income_amount"],' +
        'input[wire\\:model*="expence_amount"],' +
        'input[wire\\:model*="asset_market_amount"],' +
        'input[wire\\:model*="mortgage_amount"],' +
        'input[wire\\:model*="asset_acquisition_amount"],' +
        'input[wire\\:model*="asset_equity_amount"],' +
        'input[wire\\:model*="asset_taxable_initial_amount"],' +
        'input[wire\\:model*="asset_paid_amount"],' +
        'input[wire\\:model*="mortgage_gebyr"]'
    );

    amountFields.forEach(applyNorwegianAmountMask);
});

// Export for manual use
window.norwegianAmountMask = {
    format: formatNorwegianAmount,
    parse: parseNorwegianAmount,
    apply: applyNorwegianAmountMask
};
