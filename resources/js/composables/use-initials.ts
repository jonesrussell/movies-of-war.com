export function getInitials(fullName?: string): string {
    if (!fullName) return '';

    const names = fullName.trim().split(' ');

    if (names.length === 0) return '';
    if (names.length === 1) {
        const first = names[0];
        return first ? first.charAt(0).toUpperCase() : '';
    }

    const first = names[0];
    const last = names[names.length - 1];
    if (!first || !last) return '';

    return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase();
}

export function useInitials() {
    return { getInitials };
}
