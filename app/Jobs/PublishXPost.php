<?php

namespace App\Jobs;

use App\Models\XPost;
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

        // TODO: Implement with atymic/twitter package
        // Example approach:
        //
        // use Atymic\Twitter\Facade\Twitter;
        //
        // foreach ($this->xPost->media_urls as $mediaPath) {
        //     // Determine if it's a local file or URL
        //     if (filter_var($mediaPath, FILTER_VALIDATE_URL)) {
        //         // Download remote image first
        //         $fileContent = file_get_contents($mediaPath);
        //         $tempPath = tempnam(sys_get_temp_dir(), 'xpost_');
        //         file_put_contents($tempPath, $fileContent);
        //         $mediaPath = $tempPath;
        //     } elseif (! file_exists($mediaPath)) {
        //         // Check storage path
        //         $mediaPath = storage_path('app/public/' . $mediaPath);
        //     }
        //
        //     if (! file_exists($mediaPath)) {
        //         throw new Exception("Media file not found: {$mediaPath}");
        //     }
        //
        //     // Upload using Twitter facade (v1.1 media endpoint)
        //     $upload = Twitter::uploadMedia(['media' => $mediaPath]);
        //
        //     if (isset($upload->media_id_string)) {
        //         $mediaIds[] = $upload->media_id_string;
        //     }
        //
        //     // Clean up temp file if created
        //     if (isset($tempPath) && file_exists($tempPath)) {
        //         unlink($tempPath);
        //     }
        // }

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

        // TODO: Implement with atymic/twitter package
        // Example approach:
        //
        // use Atymic\Twitter\Facade\Twitter;
        //
        // foreach ($threadContent as $index => $content) {
        //     $params = ['text' => $content];
        //
        //     // Attach media to first tweet only
        //     if ($index === 0 && ! empty($mediaIds)) {
        //         $params['media'] = ['media_ids' => $mediaIds];
        //     }
        //
        //     // For thread replies, reference previous tweet
        //     if ($previousTweetId !== null) {
        //         $params['reply'] = ['in_reply_to_tweet_id' => $previousTweetId];
        //     }
        //
        //     // Post tweet using API v2
        //     $response = Twitter::forApiV2()->tweet()->performRequest('POST', $params);
        //
        //     if (! isset($response['data']['id'])) {
        //         throw new Exception('Failed to get tweet ID from X API response');
        //     }
        //
        //     $tweetId = $response['data']['id'];
        //
        //     // Store the first tweet ID to return
        //     if ($firstTweetId === null) {
        //         $firstTweetId = $tweetId;
        //     }
        //
        //     $previousTweetId = $tweetId;
        //
        //     // Rate limiting: avoid hitting API limits
        //     if (count($threadContent) > 1 && $index < count($threadContent) - 1) {
        //         sleep(1); // Brief pause between thread tweets
        //     }
        // }

        // TEMPORARY: For development, return a fake tweet ID
        // Remove this when implementing actual API calls
        $firstTweetId = 'fake_'.time();

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
