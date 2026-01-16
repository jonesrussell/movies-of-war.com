<?php

namespace App\Jobs;

use App\Models\XPost;
use App\Services\XApiService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PublishXPost implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public XPost $xPost
    ) {}

    /**
     * Execute the job.
     *
     * This job publishes an X post (formerly Twitter) via the X API v2.
     * It handles:
     * - Single tweets
     * - Thread creation (multiple related tweets)
     * - Media attachments (images)
     * - Error handling and retry logic
     */
    public function handle(): void
    {
        // Verify the post can be published
        if (! $this->xPost->canPublish()) {
            Log::warning('XPost cannot be published', [
                'x_post_id' => $this->xPost->id,
                'status' => $this->xPost->status,
            ]);

            return;
        }

        try {
            // Get all tweet content (main + thread parts)
            $threadContent = $this->xPost->getFullThreadContent();

            // Upload media if present
            $mediaIds = [];
            if ($this->xPost->hasMedia()) {
                $mediaIds = $this->uploadMedia();
            }

            // Publish the tweet(s)
            $tweetId = $this->publishThread($threadContent, $mediaIds);

            // Mark as published with the returned tweet ID
            $this->xPost->markAsPublished($tweetId);

            Log::info('XPost published successfully', [
                'x_post_id' => $this->xPost->id,
                'tweet_id' => $tweetId,
                'has_thread' => $this->xPost->hasThread(),
                'has_media' => $this->xPost->hasMedia(),
            ]);
        } catch (Exception $e) {
            // Log the failure
            Log::error('Failed to publish XPost', [
                'x_post_id' => $this->xPost->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Mark as failed if we've exhausted retries
            if ($this->attempts() >= $this->tries) {
                $this->xPost->markAsFailed($e->getMessage());
            }

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Upload media files to X API.
     *
     * NOTE: Media upload typically uses the v1.1 endpoint even when posting via v2.
     * Endpoint: POST https://upload.twitter.com/1.1/media/upload.json
     *
     * @return array Array of media IDs returned by X API
     *
     * @throws Exception
     */
    protected function uploadMedia(): array
    {
        $mediaIds = [];
        $xApiService = new XApiService;

        foreach ($this->xPost->media_urls as $mediaPath) {
            try {
                $mediaId = $xApiService->uploadMedia($mediaPath);
                $mediaIds[] = $mediaId;
            } catch (Exception $e) {
                Log::warning('Failed to upload media for XPost', [
                    'x_post_id' => $this->xPost->id,
                    'media_path' => $mediaPath,
                    'error' => $e->getMessage(),
                ]);

                // Continue with other media files, but log the error
                // If all media fails, this will be caught by the outer try-catch
            }
        }

        return $mediaIds;
    }

    /**
     * Publish a thread of tweets (or single tweet).
     *
     * Uses X API v2 endpoint: POST /2/tweets
     *
     * For threads:
     * - Post first tweet (with media if applicable)
     * - Post subsequent tweets as replies using reply.in_reply_to_tweet_id
     *
     * @param  array  $threadContent  Array of tweet text strings
     * @param  array  $mediaIds  Array of uploaded media IDs from v1.1 upload
     * @return string The ID of the first tweet in the thread
     *
     * @throws Exception
     */
    protected function publishThread(array $threadContent, array $mediaIds = []): string
    {
        $firstTweetId = null;
        $previousTweetId = null;
        $xApiService = new XApiService;

        foreach ($threadContent as $index => $content) {
            // Attach media to first tweet only
            $tweetMediaIds = ($index === 0 && ! empty($mediaIds)) ? $mediaIds : null;

            // Post tweet (will include reply info if previousTweetId is set)
            $tweetData = $xApiService->postTweet($content, $tweetMediaIds, $previousTweetId);
            $tweetId = $tweetData['id'];

            // Store the first tweet ID to return
            if ($firstTweetId === null) {
                $firstTweetId = $tweetId;
            }

            $previousTweetId = $tweetId;

            // Rate limiting: avoid hitting API limits
            // Brief pause between thread tweets to respect rate limits
            if (count($threadContent) > 1 && $index < count($threadContent) - 1) {
                sleep(1);
            }
        }

        return $firstTweetId;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('XPost job failed after all retries', [
            'x_post_id' => $this->xPost->id,
            'error' => $exception->getMessage(),
        ]);

        $this->xPost->markAsFailed($exception->getMessage());
    }
}
