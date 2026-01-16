<?php

namespace App\Services;

use Atymic\Twitter\Facade\Twitter;
use Exception;
use Illuminate\Support\Facades\Log;

class XApiService
{
    /**
     * Post a tweet using X API v2.
     *
     * @param  string  $text  The tweet content (max 280 characters)
     * @param  array|null  $mediaIds  Optional array of media IDs to attach
     * @param  string|null  $replyToTweetId  Optional tweet ID to reply to
     * @return array Response data with tweet ID
     *
     * @throws Exception
     */
    public function postTweet(string $text, ?array $mediaIds = null, ?string $replyToTweetId = null): array
    {
        try {
            $params = ['text' => $text];

            if (! empty($mediaIds)) {
                $params['media'] = ['media_ids' => $mediaIds];
            }

            if ($replyToTweetId !== null) {
                $params['reply'] = ['in_reply_to_tweet_id' => $replyToTweetId];
            }

            $response = Twitter::forApiV2()->tweet()->performRequest('POST', $params);

            if (! isset($response['data']['id'])) {
                throw new Exception('Failed to get tweet ID from X API response: '.json_encode($response));
            }

            return $response['data'];
        } catch (Exception $e) {
            Log::error('X API: Failed to post tweet', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
                'has_media' => ! empty($mediaIds),
                'reply_to' => $replyToTweetId,
            ]);

            throw $e;
        }
    }

    /**
     * Upload media file to X API v1.1.
     *
     * @param  string  $mediaPath  Local file path or URL
     * @return string Media ID string
     *
     * @throws Exception
     */
    public function uploadMedia(string $mediaPath): string
    {
        try {
            // Handle remote URLs
            if (filter_var($mediaPath, FILTER_VALIDATE_URL)) {
                $fileContent = file_get_contents($mediaPath);
                $tempPath = tempnam(sys_get_temp_dir(), 'xpost_media_');
                file_put_contents($tempPath, $fileContent);
                $mediaPath = $tempPath;
                $isTempFile = true;
            } else {
                // Check if it's a storage path
                if (! file_exists($mediaPath)) {
                    $storagePath = storage_path('app/public/'.$mediaPath);
                    if (file_exists($storagePath)) {
                        $mediaPath = $storagePath;
                    }
                }

                $isTempFile = false;
            }

            if (! file_exists($mediaPath)) {
                throw new Exception("Media file not found: {$mediaPath}");
            }

            // Upload using Twitter facade (v1.1 media endpoint)
            $upload = Twitter::uploadMedia(['media' => $mediaPath]);

            // Clean up temp file if created
            if (isset($isTempFile) && $isTempFile && file_exists($mediaPath)) {
                @unlink($mediaPath);
            }

            if (! isset($upload->media_id_string)) {
                throw new Exception('Failed to get media ID from X API response');
            }

            return $upload->media_id_string;
        } catch (Exception $e) {
            Log::error('X API: Failed to upload media', [
                'error' => $e->getMessage(),
                'media_path' => $mediaPath,
            ]);

            throw $e;
        }
    }

    /**
     * Search recent tweets using X API v2.
     *
     * @param  string  $query  Search query (e.g., "#WarMovies", "Saving Private Ryan")
     * @param  array  $options  Additional search options (max_results, since_id, etc.)
     * @return array Response data with tweets
     *
     * @throws Exception
     */
    public function searchTweets(string $query, array $options = []): array
    {
        try {
            $params = array_merge([
                'query' => $query,
                'max_results' => $options['max_results'] ?? 10,
                'tweet.fields' => 'created_at,author_id,public_metrics,text',
                'expansions' => 'author_id',
                'user.fields' => 'username,name',
            ], $options);

            $response = Twitter::forApiV2()->tweet()->searchRecent($params);

            return $response;
        } catch (Exception $e) {
            Log::error('X API: Failed to search tweets', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            throw $e;
        }
    }

    /**
     * Get tweet metrics using X API v2.
     *
     * @param  string  $tweetId  The tweet ID
     * @return array Tweet data with metrics
     *
     * @throws Exception
     */
    public function getTweetMetrics(string $tweetId): array
    {
        try {
            $params = [
                'ids' => $tweetId,
                'tweet.fields' => 'public_metrics,created_at',
            ];

            $response = Twitter::forApiV2()->tweet()->findById($tweetId, $params);

            if (! isset($response['data'])) {
                throw new Exception('Failed to get tweet metrics from X API response');
            }

            return $response['data'];
        } catch (Exception $e) {
            Log::error('X API: Failed to get tweet metrics', [
                'error' => $e->getMessage(),
                'tweet_id' => $tweetId,
            ]);

            throw $e;
        }
    }

    /**
     * Get mentions of the authenticated user using X API v2.
     *
     * @param  array  $options  Additional options (max_results, since_id, etc.)
     * @return array Response data with mentions
     *
     * @throws Exception
     */
    public function getMentions(array $options = []): array
    {
        try {
            $userId = config('services.twitter.user_id') ?? Twitter::forApiV2()->user()->getMe()['data']['id'];

            $params = array_merge([
                'max_results' => $options['max_results'] ?? 10,
                'tweet.fields' => 'created_at,author_id,public_metrics,text,in_reply_to_user_id',
                'expansions' => 'author_id',
                'user.fields' => 'username,name',
            ], $options);

            $response = Twitter::forApiV2()->user()->getMentions($userId, $params);

            return $response;
        } catch (Exception $e) {
            Log::error('X API: Failed to get mentions', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get user information by username.
     *
     * @param  string  $username  The username (without @)
     * @return array User data
     *
     * @throws Exception
     */
    public function getUserByUsername(string $username): array
    {
        try {
            $params = [
                'user.fields' => 'id,username,name,public_metrics,created_at',
            ];

            $response = Twitter::forApiV2()->user()->findByUsername($username, $params);

            if (! isset($response['data'])) {
                throw new Exception('Failed to get user from X API response');
            }

            return $response['data'];
        } catch (Exception $e) {
            Log::error('X API: Failed to get user by username', [
                'error' => $e->getMessage(),
                'username' => $username,
            ]);

            throw $e;
        }
    }

    /**
     * Check rate limit status.
     * Note: This requires implementing rate limit tracking or using response headers.
     *
     * @return array Rate limit information
     */
    public function getRateLimitStatus(): array
    {
        // Rate limits are typically returned in response headers
        // For now, return empty array - can be enhanced with actual tracking
        return [];
    }
}
