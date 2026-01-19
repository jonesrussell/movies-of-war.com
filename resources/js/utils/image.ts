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
 * Generate srcset for local poster files
 */
export function getLocalPosterSrcset(
    posterPath: string | null,
    context: 'grid' | 'detail' | 'hero',
    format: 'avif' | 'webp' | 'original' = 'original',
): string {
    if (!posterPath) {
        return '';
    }

    // Extract base filename from poster_path (e.g., 'posters/abc123.jpg' -> 'abc123')
    const pathParts = posterPath.split('/');
    const filename = pathParts[pathParts.length - 1] || '';
    const baseFilename = filename.replace(/\.(jpg|jpeg|png|webp)$/i, '');

    const sizes = {
        grid: [185, 342],
        detail: [342, 500, 780],
        hero: [500, 780],
    };

    const sizeList = sizes[context];
    const extension =
        format === 'original' ? getFileExtension(posterPath) : format;

    return sizeList
        .map((width) => {
            const url = `/storage/posters/${baseFilename}-${width}.${extension}`;
            return `${url} ${width}w`;
        })
        .join(', ');
}

/**
 * Get file extension from a path
 */
function getFileExtension(path: string): string {
    const match = path.match(/\.([^.]+)$/);
    return match && match[1] ? match[1].toLowerCase() : 'jpg';
}

/**
 * Generate srcset for TMDB poster images (fallback when local files not available)
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
 * Generate srcset for poster images (local files preferred, fallback to TMDB)
 */
export function getPosterSrcset(
    localPosterPath: string | null,
    tmdbPosterPath: string | null,
    context: 'grid' | 'detail' | 'hero',
    format: 'avif' | 'webp' | 'original' = 'original',
): string {
    // Prefer local files if available
    if (localPosterPath) {
        return getLocalPosterSrcset(localPosterPath, context, format);
    }

    // Fallback to TMDB URLs
    if (tmdbPosterPath) {
        const useWebP = format === 'webp';
        return getTmdbPosterSrcset(tmdbPosterPath, context, useWebP);
    }

    return '';
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

    // If it's a local storage path, return as-is
    if (
        posterUrl.startsWith('posters/') ||
        posterUrl.includes('/storage/posters/')
    ) {
        // Normalize to 'posters/filename.ext' format
        const match = posterUrl.match(/posters\/(.+)$/);
        return match ? `posters/${match[1]}` : null;
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

    // If it's already a path starting with / (and not a local path), it might be a TMDB path
    if (
        posterUrl.startsWith('/') &&
        !posterUrl.startsWith('/posters/') &&
        !posterUrl.startsWith('/storage/')
    ) {
        return posterUrl;
    }

    // Local storage URLs/paths are not TMDB paths, return null
    return null;
}

/**
 * Get local poster URL for a specific size
 */
export function getLocalPosterUrl(
    posterPath: string | null,
    width: number,
    format: 'avif' | 'webp' | 'original' = 'original',
): string {
    if (!posterPath) {
        return '/images/placeholders/poster-placeholder.png';
    }

    // Extract base filename from poster_path
    const pathParts = posterPath.split('/');
    const filename = pathParts[pathParts.length - 1] || '';
    const baseFilename = filename.replace(/\.(jpg|jpeg|png|webp)$/i, '');
    const extension =
        format === 'original' ? getFileExtension(posterPath) : format;

    return `/storage/posters/${baseFilename}-${width}.${extension}`;
}
