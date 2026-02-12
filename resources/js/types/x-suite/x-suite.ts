export enum XPostStatus {
    Draft = 'draft',
    Scheduled = 'scheduled',
    Published = 'published',
    Failed = 'failed',
    Cancelled = 'cancelled',
}

export interface XPost {
    id: number;
    content: string | null;
    thread_parts: string[] | null;
    media_urls: string[] | null;
    status: XPostStatus;
    scheduled_for: string | null;
    published_at: string | null;
    x_post_id: string | null;
    error_message: string | null;
    user_id: number | null;
    created_at: string;
    updated_at: string;
    user?: { id: number; name: string; email: string };
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
