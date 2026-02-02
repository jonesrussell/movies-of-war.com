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
 * Format release date for display.
 * Uses full date when available (e.g. "Mar 15, 2025"), otherwise year only (e.g. "2025").
 */
export function formatReleaseDate(
    releaseDate: string | null | undefined,
    releaseYear: number | null | undefined,
): string {
    if (releaseDate && /^\d{4}-\d{2}-\d{2}$/.test(releaseDate)) {
        try {
            // Append noon UTC to avoid timezone shifting date (e.g. 2024-01-01 UTC midnight → Dec 31 in PST)
            const date = new Date(`${releaseDate}T12:00:00Z`);
            if (!Number.isNaN(date.getTime())) {
                return new Intl.DateTimeFormat('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                }).format(date);
            }
        } catch {
            // Fall through to year
        }
    }
    return releaseYear != null ? String(releaseYear) : '';
}

/**
 * Convert ISO 639-1 language code to readable name.
 * Guards for environments where Intl.DisplayNames might not exist (SSR, older browsers).
 */
export function getLanguageName(isoCode: string): string {
    if (!isoCode) return isoCode;
    try {
        if (typeof Intl !== 'undefined' && Intl.DisplayNames) {
            return (
                new Intl.DisplayNames(['en'], { type: 'language' }).of(
                    isoCode,
                ) ?? isoCode
            );
        }
    } catch {
        // Fallback to raw code if Intl fails
    }
    return isoCode;
}
