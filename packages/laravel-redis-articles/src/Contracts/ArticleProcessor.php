<?php

namespace JonesRussell\LaravelRedisArticles\Contracts;

interface ArticleProcessor
{
    /**
     * Process an incoming article from Redis.
     */
    public function process(array $articleData): ?ArticleModel;

    /**
     * Validate the incoming article data.
     */
    public function validate(array $articleData): bool;

    /**
     * Check if an article already exists by external ID.
     */
    public function exists(string $externalId): bool;
}
