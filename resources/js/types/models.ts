export interface Movie {
    id: number;
    title: string;
    slug: string;
    release_year: number;
    release_date: string | null;
    synopsis: string;
    runtime: number | null;
    country: string | null;
    conflict: string | null;
    poster_path: string | null;
    poster_url: string | null;
    trailer_url: string | null;
    imdb_id: string | null;
    is_upcoming: boolean;
    status?: 'draft' | 'published' | 'archived';
    created_at: string;
    updated_at: string;
    tags?: Tag[];
    is_watchlisted?: boolean;
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
    type: 'genre' | 'theme' | 'era';
    created_at: string;
    updated_at: string;
}

export interface FeaturedSlot {
    id: number;
    movie_id: number;
    slot: 'hero' | 'pick_of_week';
    created_at: string;
    updated_at: string;
    movie?: Movie;
}

export interface PaginatedMovies {
    data: Movie[];
    links: PaginationLinks;
    meta: PaginationMeta;
}

export interface PaginatedFeaturedSlots {
    data: FeaturedSlot[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface PaginationLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

export interface MovieFilters {
    search?: string;
    year?: number | string;
    country?: string;
    conflict?: string;
    tag?: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    avatar?: string | null;
    email_verified_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
    updated_at: string;
}

export interface XPost {
    id: number;
    content: string | null;
    thread_parts: string[] | null;
    media_urls: string[] | null;
    status: 'draft' | 'scheduled' | 'published' | 'failed' | 'cancelled';
    scheduled_for: string | null;
    published_at: string | null;
    x_post_id: string | null;
    error_message: string | null;
    user_id: number | null;
    created_at: string;
    updated_at: string;
    user?: User;
}

export interface PaginatedXPosts {
    data: XPost[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface XAutoReplyRule {
    id: number;
    name: string;
    trigger_type: 'mention' | 'hashtag' | 'keyword';
    trigger_keywords: string[] | null;
    reply_template: string;
    is_active: boolean;
    priority: number;
    created_at: string;
    updated_at: string;
}

export interface PaginatedXAutoReplyRules {
    data: XAutoReplyRule[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface XCuratedPost {
    id: number;
    tweet_id: string;
    author_username: string;
    content: string;
    media_urls: string[] | null;
    like_count: number;
    retweet_count: number;
    discovered_at: string;
    is_featured: boolean;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedXCuratedPosts {
    data: XCuratedPost[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface XTrendKeyword {
    id: number;
    keyword: string;
    type: 'keyword' | 'hashtag' | 'phrase';
    is_active: boolean;
    results_count: number | null;
    last_checked_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedXTrendKeywords {
    data: XTrendKeyword[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface XTrendResult {
    id: number;
    keyword_id: number;
    tweet_id: string;
    author_username: string;
    content: string;
    like_count: number;
    retweet_count: number;
    discovered_at: string;
    created_at: string;
    updated_at: string;
}
