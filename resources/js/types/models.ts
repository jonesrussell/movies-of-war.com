export interface Movie {
  id: number
  title: string
  slug: string
  release_year: number
  release_date: string | null
  synopsis: string
  runtime: number | null
  country: string | null
  conflict: string | null
  poster_path: string | null
  poster_url: string | null
  trailer_url: string | null
  imdb_id: string | null
  is_upcoming: boolean
  status?: 'draft' | 'published' | 'archived'
  created_at: string
  updated_at: string
  tags?: Tag[]
  is_watchlisted?: boolean
}

export interface Tag {
  id: number
  name: string
  slug: string
  type: 'genre' | 'theme' | 'era'
  created_at: string
  updated_at: string
}

export interface FeaturedSlot {
  id: number
  movie_id: number
  slot: 'hero' | 'pick_of_week'
  starts_at: string
  ends_at: string | null
  created_at: string
  updated_at: string
  movie?: Movie
}

export interface PaginatedMovies {
  data: Movie[]
  links: PaginationLinks
  meta: PaginationMeta
}

export interface PaginatedFeaturedSlots {
  data: FeaturedSlot[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  links: Array<{
    url: string | null
    label: string
    active: boolean
  }>
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  links: Array<{
    url: string | null
    label: string
    active: boolean
  }>
  path: string
  per_page: number
  to: number | null
  total: number
}

export interface MovieFilters {
  search?: string
  year?: number | string
  country?: string
  conflict?: string
  tag?: string
}
