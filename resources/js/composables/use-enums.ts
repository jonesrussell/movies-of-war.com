import { MovieStatus, TagType, XPostStatus } from '@/types/enums';

/**
 * Composable for enum utilities matching PHP enum methods.
 */
export function useEnums() {
    /**
     * Get MovieStatus label.
     */
    function getMovieStatusLabel(status: MovieStatus): string {
        switch (status) {
            case MovieStatus.Draft:
                return 'Draft';
            case MovieStatus.Published:
                return 'Published';
            case MovieStatus.Archived:
                return 'Archived';
            default:
                return String(status);
        }
    }

    /**
     * Get MovieStatus color class.
     */
    function getMovieStatusColor(status: MovieStatus): string {
        switch (status) {
            case MovieStatus.Draft:
                return 'yellow';
            case MovieStatus.Published:
                return 'green';
            case MovieStatus.Archived:
                return 'zinc';
            default:
                return 'zinc';
        }
    }

    /**
     * Get MovieStatus badge class.
     */
    function getMovieStatusBadgeClass(status: MovieStatus): string {
        switch (status) {
            case MovieStatus.Draft:
                return 'bg-yellow-900/50 text-yellow-300';
            case MovieStatus.Published:
                return 'bg-green-900/50 text-green-300';
            case MovieStatus.Archived:
                return 'bg-zinc-800 text-zinc-400';
            default:
                return 'bg-zinc-800 text-zinc-400';
        }
    }

    /**
     * Get XPostStatus label.
     */
    function getXPostStatusLabel(status: XPostStatus): string {
        switch (status) {
            case XPostStatus.Draft:
                return 'Draft';
            case XPostStatus.Scheduled:
                return 'Scheduled';
            case XPostStatus.Published:
                return 'Published';
            case XPostStatus.Failed:
                return 'Failed';
            case XPostStatus.Cancelled:
                return 'Cancelled';
            default:
                return String(status);
        }
    }

    /**
     * Get XPostStatus color class.
     */
    function getXPostStatusColor(status: XPostStatus): string {
        switch (status) {
            case XPostStatus.Draft:
                return 'zinc';
            case XPostStatus.Scheduled:
                return 'blue';
            case XPostStatus.Published:
                return 'green';
            case XPostStatus.Failed:
                return 'red';
            case XPostStatus.Cancelled:
                return 'yellow';
            default:
                return 'zinc';
        }
    }

    /**
     * Get XPostStatus badge class.
     */
    function getXPostStatusBadgeClass(status: XPostStatus): string {
        switch (status) {
            case XPostStatus.Draft:
                return 'bg-yellow-900/50 text-yellow-300';
            case XPostStatus.Scheduled:
                return 'bg-blue-900/50 text-blue-300';
            case XPostStatus.Published:
                return 'bg-green-900/50 text-green-300';
            case XPostStatus.Failed:
                return 'bg-red-900/50 text-red-300';
            case XPostStatus.Cancelled:
                return 'bg-zinc-800 text-zinc-400';
            default:
                return 'bg-zinc-800 text-zinc-400';
        }
    }

    /**
     * Get TagType label.
     */
    function getTagTypeLabel(type: TagType): string {
        switch (type) {
            case TagType.Genre:
                return 'Genre';
            case TagType.Theme:
                return 'Theme';
            case TagType.Era:
                return 'Era';
            default:
                return String(type);
        }
    }

    /**
     * Get TagType color class.
     */
    function getTagTypeColor(type: TagType): string {
        switch (type) {
            case TagType.Genre:
                return 'blue';
            case TagType.Theme:
                return 'purple';
            case TagType.Era:
                return 'amber';
            default:
                return 'zinc';
        }
    }

    return {
        getMovieStatusLabel,
        getMovieStatusColor,
        getMovieStatusBadgeClass,
        getXPostStatusLabel,
        getXPostStatusColor,
        getXPostStatusBadgeClass,
        getTagTypeLabel,
        getTagTypeColor,
    };
}
