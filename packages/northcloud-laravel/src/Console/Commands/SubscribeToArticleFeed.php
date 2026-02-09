<?php

namespace JonesRussell\NorthcloudLaravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JonesRussell\NorthcloudLaravel\Events\ArticleReceived;
use JonesRussell\NorthcloudLaravel\Jobs\ProcessIncomingArticle;

class SubscribeToArticleFeed extends Command
{
    protected $signature = 'articles:subscribe {channel? : The Redis channel to subscribe to}';

    protected $description = 'Subscribe to Redis pub/sub channel for incoming articles';

    public function handle(): void
    {
        $channel = $this->argument('channel') ?? config('northcloud.redis.channel', 'articles:default');
        $minQualityScore = (int) config('northcloud.quality.min_score', 0);
        $qualityFilterEnabled = config('northcloud.quality.enabled', false);

        $this->info("Subscribing to Redis channel: {$channel}");

        if ($qualityFilterEnabled && $minQualityScore > 0) {
            $this->info("Filtering articles with quality_score >= {$minQualityScore}");
        }

        $redisConnection = config('northcloud.redis.connection', 'default');
        $redisConfig = config("database.redis.{$redisConnection}");
        $client = new \Redis;

        $host = $redisConfig['host'] ?? '127.0.0.1';
        $port = (int) ($redisConfig['port'] ?? 6379);
        $password = $redisConfig['password'] ?? null;

        $client->connect($host, $port);

        if ($password) {
            $client->auth($password);
        }

        $readTimeout = $redisConfig['read_timeout'] ?? -1;
        $client->setOption(\Redis::OPT_READ_TIMEOUT, (float) $readTimeout);

        $this->info('Connected to Redis. Listening for articles...');

        $client->subscribe([$channel], function (\Redis $redis, string $receivedChannel, string $message) use ($minQualityScore, $qualityFilterEnabled, $channel) {
            $this->processMessage($message, $minQualityScore, $qualityFilterEnabled, $channel);
        });
    }

    protected function processMessage(string $message, int $minQualityScore, bool $qualityFilterEnabled, string $channel): void
    {
        try {
            $data = json_decode($message, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Redis message', [
                    'error' => json_last_error_msg(),
                    'message_preview' => substr($message, 0, 200),
                ]);

                return;
            }

            if (! $this->isValidMessage($data)) {
                Log::warning('Invalid publisher message format', [
                    'data_keys' => array_keys($data),
                ]);

                return;
            }

            if ($qualityFilterEnabled && $minQualityScore > 0 && ($data['quality_score'] ?? 0) < $minQualityScore) {
                Log::warning('Skipping article with quality score below threshold', [
                    'quality_score' => $data['quality_score'],
                    'min_quality_score' => $minQualityScore,
                ]);

                return;
            }

            $title = $data['title'] ?? $data['og_title'] ?? 'Untitled';
            $externalId = $data['id'] ?? 'unknown';

            Log::info('Received article from Redis', [
                'external_id' => $externalId,
                'title' => $title,
                'channel' => $channel,
            ]);

            event(new ArticleReceived($data, $channel));

            $processSync = config('northcloud.processing.sync', true);

            if ($processSync) {
                ProcessIncomingArticle::dispatchSync($data);
            } else {
                ProcessIncomingArticle::dispatch($data);
            }

            $this->info("✓ Processed article: {$title}");
        } catch (\Exception $e) {
            Log::error('Failed to process Redis message', [
                'error' => $e->getMessage(),
                'message_preview' => substr($message, 0, 200),
            ]);
        }
    }

    protected function isValidMessage(array $data): bool
    {
        if (! isset($data['id']) || ! isset($data['publisher']) || ! is_array($data['publisher'])) {
            return false;
        }

        if (! isset($data['title']) && ! isset($data['og_title'])) {
            return false;
        }

        return true;
    }
}
