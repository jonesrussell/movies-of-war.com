<?php

namespace JonesRussell\NorthcloudLaravel\Contracts;

interface ArticleModel
{
    /**
     * Get the external ID of the article.
     */
    public function getExternalId(): string;

    /**
     * Get the title of the article.
     */
    public function getTitle(): string;

    /**
     * Get the article URL.
     */
    public function getUrl(): ?string;

    /**
     * Get the article status.
     */
    public function getStatus(): string;

    /**
     * Check if the article is published.
     */
    public function isPublished(): bool;
}
