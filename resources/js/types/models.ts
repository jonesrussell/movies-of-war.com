import { type MovieStatus, type TagType } from './enums';

export interface CastMember {
    tmdb_id: number;
    name: string;
    character: string | null;
    order: number;
    profile_path: string | null;
    slug?: string;
}

export interface CrewMember {
    tmdb_id: number;
    name: string;
    job: string;
    department: string | null;
    profile_path: string | null;
    slug?: string;
}

export interface UserReviewSummary {
    id: number;
    rating: number;
    movie: { slug: string };
}

export interface Movie {
    id: number;
    tmdb_id: number | null;
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
    tmdb_vote_average?: number | null;
    tmdb_vote_count?: number | null;
    director?: string | null;
    writers?: string[] | null;
    production_status?: string | null;
    original_language?: string | null;
    budget?: number | null;
    revenue?: number | null;
    cast?: CastMember[];
    crew?: CrewMember[];
    is_upcoming: boolean;
    status?: MovieStatus;
    created_at: string;
    updated_at: string;
    tags?: Tag[];
    is_watchlisted?: boolean;
    user_review?: UserReviewSummary | null;
}

export interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string | null;
    url: string;
    image_url: string | null;
    author: string | null;
    status: string;
    published_at: string | null;
    view_count: number;
    is_featured: boolean;
    news_source?: {
        id: number;
        name: string;
        slug: string;
    };
    tags?: Tag[];
    articleable_type: string | null;
    articleable_id: number | null;
    articleable?: Movie | null;
    created_at: string;
    updated_at: string;
}

export interface FilmographyEntry {
    movie_id: number;
    movie_title: string;
    movie_slug: string;
    release_year: number | null;
    poster_url: string | null;
    character?: string | null;
    cast_order?: number | null;
    job?: string;
}

export interface Person {
    id: number;
    tmdb_id: number;
    name: string;
    slug: string;
    profile_path: string | null;
    biography: string | null;
    birthday: string | null;
    deathday: string | null;
    place_of_birth: string | null;
    also_known_as: string[] | null;
    known_for: FilmographyEntry[];
    filmography_cast: FilmographyEntry[];
    filmography_crew: FilmographyEntry[];
    created_at: string;
    updated_at: string;
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
    type: TagType;
    created_at: string;
    updated_at: string;
}

export interface ReviewAuthor {
    id: number;
    name: string;
}

export interface ReviewMovie {
    id: number;
    slug: string;
    title: string;
    poster_url?: string | null;
}

export interface Review {
    id: number;
    user_id: number;
    movie_id: number;
    movie?: ReviewMovie;
    rating: number;
    title: string | null;
    content: string;
    content_html: string;
    content_excerpt: string;
    has_spoilers: boolean;
    is_published: boolean;
    helpful_count: number;
    comments_count: number;
    created_at: string;
    updated_at: string;
    stars: string;
    is_edited: boolean;
    formatted_date: string;
    user?: ReviewAuthor;
    is_curator?: boolean;
    can_edit: boolean;
    can_delete: boolean;
    comments?: ReviewComment[];
}

export interface ReviewComment {
    id: number;
    review_id: number;
    user_id: number;
    content: string;
    created_at: string;
    updated_at: string;
    user?: ReviewAuthor;
    can_edit: boolean;
    can_delete: boolean;
}

export interface ReviewsSummary {
    user_rating_average: number | null;
    user_rating_count: number;
}

export interface FilesystemCuratorReview {
    title: string;
    year: number;
    rating: number;
    director: string | null;
    starring: string[];
    runtime: number | null;
    slug: string;
    has_spoilers: boolean;
    content_html: string;
    content_excerpt: string;
}

export interface MovieReviewsData {
    summary: ReviewsSummary;
    user_review: Review | null;
    recent: Review[];
    curator_review?: Review | null;
}

export type SlotType = 'hero' | 'pick_of_week';
export type SelectionMethod = 'auto' | 'manual';

export interface FeaturedSlot {
    id: number;
    movie_id: number;
    slot: SlotType;
    created_at: string;
    updated_at: string;
    movie?: Movie;
}

export interface FeaturedSlotQueue {
    id: number;
    movie_id: number;
    slot: SlotType;
    position: number;
    selection_method: SelectionMethod;
    scheduled_for: string;
    created_at: string;
    updated_at: string;
    movie?: Movie;
}

export interface FeaturedSlotHistory {
    id: number;
    movie_id: number | null;
    slot: SlotType;
    selection_method: SelectionMethod;
    started_at: string;
    ended_at: string | null;
    created_at: string;
    updated_at: string;
    movie?: Movie | null;
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

export type MovieSortOption =
    | 'release_year_desc'
    | 'release_year_asc'
    | 'title_asc'
    | 'title_desc'
    | 'created_at_desc';

export interface MovieFilters {
    search?: string;
    year?: number | string;
    country?: string;
    conflict?: string;
    tag?: string;
    sort?: MovieSortOption;
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
