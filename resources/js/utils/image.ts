/**
 * Image utility functions for generating responsive image URLs and srcsets
 */

const TMDB_IMAGE_BASE_URL = 'https://image.tmdb.org/t/p';

/**
 * Generate TMDB poster URL with optional size and WebP format
 */
export function getTmdbPosterUrl(
    posterPath: string,
    size: string = 'w500',
    useWebP: boolean = false,
): string {
    if (!posterPath) {
        return '';
    }

    const url = `${TMDB_IMAGE_BASE_URL}/${size}${posterPath}`;
    return useWebP ? `${url}.webp` : url;
}

/**
 * Generate srcset for TMDB poster images
 */
export function getTmdbPosterSrcset(
    posterPath: string | null,
    context: 'grid' | 'detail' | 'hero',
    useWebP: boolean = false,
): string {
    if (!posterPath) {
        return '';
    }

    const sizes = {
        grid: ['w185', 'w342'],
        detail: ['w342', 'w500', 'w780'],
        hero: ['w500', 'w780', 'original'],
    };

    const sizeList = sizes[context];
    const extension = useWebP ? '.webp' : '';

    return sizeList
        .map((size) => {
            const url = `${TMDB_IMAGE_BASE_URL}/${size}${posterPath}${extension}`;
            // Extract width from size (e.g., 'w185' -> '185')
            const width = size === 'original' ? '1920' : size.replace('w', '');
            return `${url} ${width}w`;
        })
        .join(', ');
}

/**
 * Get sizes attribute for responsive images based on context
 */
export function getTmdbPosterSizes(
    context: 'grid' | 'detail' | 'hero',
): string {
    const sizes = {
        grid: '(max-width: 640px) 33vw, (max-width: 1024px) 25vw, 16vw',
        detail: '(max-width: 768px) 100vw, 33vw',
        hero: '100vw',
    };

    return sizes[context];
}

/**
 * Check if a URL is a TMDB image URL
 */
export function isTmdbImageUrl(url: string | null): boolean {
    if (!url) {
        return false;
    }

    return (
        url.includes('image.tmdb.org') || url.startsWith('/storage/posters/')
    );
}

/**
 * Extract poster path from TMDB URL or return the path if it's already a path
 */
export function extractPosterPath(posterUrl: string | null): string | null {
    if (!posterUrl) {
        return null;
    }

    // If it's already a path (starts with /), return it
    if (posterUrl.startsWith('/')) {
        return posterUrl;
    }

    // If it's a TMDB URL, extract the path
    if (posterUrl.includes('image.tmdb.org')) {
        // Remove .webp extension if present
        const cleanUrl = posterUrl.replace(/\.webp$/, '');
        const match = cleanUrl.match(/\/t\/p\/w\d+\/(.+)$/);
        if (match) {
            return `/${match[1]}`;
        }
    }

    // If it's a local storage URL, extract the path
    if (posterUrl.includes('/storage/posters/')) {
        const match = posterUrl.match(/\/storage\/posters\/(.+)$/);
        if (match) {
            return `/posters/${match[1]}`;
        }
    }

    return null;
}
