/**
 * Format utilities for currency, language names, etc.
 */

/**
 * Format a number as USD currency (e.g. $12,000,000).
 */
export function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(value);
}

/**
 * Convert ISO 639-1 language code to readable name.
 * Guards for environments where Intl.DisplayNames might not exist (SSR, older browsers).
 */
export function getLanguageName(isoCode: string): string {
    if (!isoCode) return isoCode;
    try {
        if (typeof Intl !== 'undefined' && Intl.DisplayNames) {
            return new Intl.DisplayNames(['en'], { type: 'language' }).of(
                isoCode,
            ) ?? isoCode;
        }
    } catch {
        // Fallback to raw code if Intl fails
    }
    return isoCode;
}
