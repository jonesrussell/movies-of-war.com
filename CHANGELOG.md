# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-03-01

### Added

- Featured slot queue and auto-rotation system with configurable selection strategies
- Laravel Horizon integration for queue processing
- NorthCloud MCP server configuration for production access
- "Intelligence command center" public site redesign with Slate Command palette, JetBrains Mono + IBM Plex Sans typography, and custom primitives (ScanLine, CoordinateGrid)
- Public pages redesigned: Welcome, Movies (index/show), Articles (index/show), Watchlist, MovieHero, FeaturedMovie, MovieFacts, filters, header, footer, and section headers
- X (Twitter) post integration with import and admin management features
- NorthCloud sidebar auto-registration via `NorthCloud::registerNavigation()`
- Article ingestion from NorthCloud via Redis pub/sub with confidence-based movie linking
- Curator review system with Markdown rendering on movie pages
- User watchlist functionality
- TMDB movie import pipeline with multi-strategy discovery, scheduled imports, and upcoming movie graduation
- Admin dashboard with movie, featured slot, TMDB import, and article management
- SEO enhancements with structured data and meta tags across public pages
- Deployment pipeline via PHP Deployer with Caddy web server
- Movie card animations with staggered enter transitions

### Changed

- Upgraded northcloud-laravel through v0.2 to v0.7 for full platform compliance
- Extracted shared pagination into ResolvesPagination trait
- Added `declare(strict_types=1)` across all app files
- Extracted confidence thresholds to northcloud.linking config
- Switched to package northcloud-admin middleware, removing local duplicate
- Migrated from local laravel-redis-articles to northcloud-laravel package
- Switched x-suite-laravel from local path to Packagist release (^0.1)

### Fixed

- Vote count filter now skipped for upcoming movie imports
- WarArticle missing `movies()` relationship
- Preserved release_year when TMDB returns null release_date
- Intel palette made theme-aware for light mode readability
- Local poster srcsets used for hero background images
- Era tags work as quick filters on /movies page
- Homepage shows actual published film count
- Caddy ACME issuer configuration cleaned up (removed empty braces)
- NorthCloud channel configuration corrected (Layer 6 entertainment)
- Missing article_tag migration published from northcloud package
- ESLint errors resolved across dashboard pages
