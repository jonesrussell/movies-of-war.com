<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\X\XSearchResponse;
use App\Data\X\XTweetData;
use Atymic\Twitter\Facade\Twitter;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class XApiService
{
    /**
     * Post a tweet using X API v2 (OAuth 2.0 User Context required).
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
            // Ensure text is not empty
            if (empty($text)) {
                throw new Exception('Tweet text cannot be empty');
            }

            // Try OAuth 2.0 User Context Bearer token from database first (from OAuth 2.0 flow)
            $oauth2Token = \Illuminate\Support\Facades\DB::table('x_oauth2_tokens')
                ->where('expires_at', '>', now())
                ->orWhereNull('expires_at')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($oauth2Token && $oauth2Token->access_token) {
                return $this->postTweetWithOAuth2($text, $mediaIds, $replyToTweetId, $oauth2Token->access_token);
            }

            // Fallback to explicit config token (not TWITTER_BEARER_TOKEN which is app-only)
            $oauth2AccessToken = config('twitter.oauth2_access_token');
            if ($oauth2AccessToken && $oauth2AccessToken !== config('services.twitter.bearer_token')) {
                return $this->postTweetWithOAuth2($text, $mediaIds, $replyToTweetId, $oauth2AccessToken);
            }

            // OAuth 1.0a is deprecated and cannot be used for posting
            throw new Exception('OAuth 2.0 User Context access token required for posting tweets. Please complete the OAuth 2.0 flow at /x-oauth2/redirect');
        } catch (\Throwable $e) {
            $friendlyMessage = XApiErrorParser::getFriendlyMessage($e);

            Log::error('X API: Failed to post tweet', [
                'error' => $e->getMessage(),
                'friendly_message' => $friendlyMessage,
                'text_length' => strlen($text),
                'has_media' => ! empty($mediaIds),
                'reply_to' => $replyToTweetId,
            ]);

            // Throw a new exception with the friendly message (preserve original exception)
            $exception = $e instanceof Exception ? $e : new Exception($e->getMessage(), $e->getCode(), $e);
            throw new Exception($friendlyMessage, $exception->getCode(), $exception);
        }
    }

    /**
     * Upload media file to X API.
     * Note: Currently uses v1.1 endpoint (https://upload.twitter.com/1.1/media/upload.json) for simplicity.
     * X API v2 supports chunked uploads via /2/media/upload/* endpoints, but v1.1 works for images < 5MB.
     * Both endpoints require OAuth 2.0 User Context authentication.
     *
     * @param  string  $mediaPath  Local file path or URL
     * @return string Media ID string
     *
     * @throws Exception
     */
    public function uploadMedia(string $mediaPath): string
    {
        try {
            // Get OAuth 2.0 access token (required for media upload in 2026)
            $oauth2Token = \Illuminate\Support\Facades\DB::table('x_oauth2_tokens')
                ->where('expires_at', '>', now())
                ->orWhereNull('expires_at')
                ->orderBy('created_at', 'desc')
                ->first();

            $oauth2AccessToken = $oauth2Token?->access_token
                ?? (config('twitter.oauth2_access_token') !== config('services.twitter.bearer_token')
                    ? config('twitter.oauth2_access_token')
                    : null);

            if (! $oauth2AccessToken) {
                throw new Exception('OAuth 2.0 User Context access token required for media upload. Please complete the OAuth 2.0 flow at /x-oauth2/redirect');
            }

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

            // Upload using OAuth 2.0 Bearer token (X API v1.1 endpoint, works with v2 tweets)
            $upload = $this->uploadMediaWithOAuth2($mediaPath, $oauth2AccessToken);

            // Clean up temp file if created
            if (isset($isTempFile) && $isTempFile && file_exists($mediaPath)) {
                @unlink($mediaPath);
            }

            return $upload;
        } catch (\Throwable $e) {
            $friendlyMessage = XApiErrorParser::getFriendlyMessage($e);

            Log::error('X API: Failed to upload media', [
                'error' => $e->getMessage(),
                'friendly_message' => $friendlyMessage,
                'media_path' => $mediaPath,
            ]);

            // Throw a new exception with the friendly message (preserve original exception)
            $exception = $e instanceof Exception ? $e : new Exception($e->getMessage(), $e->getCode(), $e);
            throw new Exception($friendlyMessage, $exception->getCode(), $exception);
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
                'max_results' => $options['max_results'] ?? 10,
                'tweet.fields' => 'created_at,author_id,public_metrics,text',
                'expansions' => 'author_id',
                'user.fields' => 'username,name',
            ], $options);

            $response = Twitter::forApiV2()->searchRecent($query, $params);

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

            $response = Twitter::forApiV2()->findById($tweetId, $params);

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

    /**
     * Search tweets and return as DTO.
     *
     * @param  string  $query  Search query
     * @param  array<string, mixed>  $options  Additional search options
     *
     * @throws Exception
     */
    public function searchTweetsAsDto(string $query, array $options = []): XSearchResponse
    {
        $response = $this->searchTweets($query, $options);

        return XSearchResponse::fromApiResponse($response);
    }

    /**
     * Get tweet metrics as DTO.
     *
     * @param  string  $tweetId  The tweet ID
     *
     * @throws Exception
     */
    public function getTweetMetricsAsDto(string $tweetId): XTweetData
    {
        $data = $this->getTweetMetrics($tweetId);

        return XTweetData::fromApiResponse($data);
    }

    /**
     * Post a tweet using OAuth 2.0 Bearer token authentication.
     *
     * @param  string  $text  The tweet content
     * @param  array|null  $mediaIds  Optional array of media IDs
     * @param  string|null  $replyToTweetId  Optional tweet ID to reply to
     * @param  string  $accessToken  OAuth 2.0 access token
     * @return array Response data with tweet ID
     *
     * @throws Exception
     */
    protected function postTweetWithOAuth2(
        string $text,
        ?array $mediaIds,
        ?string $replyToTweetId,
        string $accessToken
    ): array {
        $client = new GuzzleClient([
            'base_uri' => 'https://api.twitter.com/2/',
        ]);

        $payload = ['text' => $text];

        if (! empty($mediaIds)) {
            $mediaIds = array_filter($mediaIds, fn ($id) => $id !== null);
            if (! empty($mediaIds)) {
                $payload['media'] = ['media_ids' => array_values($mediaIds)];
            }
        }

        if ($replyToTweetId !== null && $replyToTweetId !== '') {
            $payload['reply'] = ['in_reply_to_tweet_id' => $replyToTweetId];
        }

        $response = $client->post('tweets', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => $payload,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (! isset($data['data']['id'])) {
            throw new Exception('Failed to get tweet ID from X API response: '.json_encode($data));
        }

        return $data['data'];
    }

    /**
     * Upload media using OAuth 2.0 Bearer token.
     * Uses X API v1.1 media endpoint (compatible with v2 tweets).
     * For larger files or v2 chunked uploads, see /2/media/upload/* endpoints.
     *
     * @param  string  $mediaPath  Local file path
     * @param  string  $accessToken  OAuth 2.0 User Context access token
     * @return string Media ID string
     *
     * @throws Exception
     */
    protected function uploadMediaWithOAuth2(string $mediaPath, string $accessToken): string
    {
        $client = new GuzzleClient([
            'base_uri' => 'https://upload.twitter.com/1.1/',
        ]);

        // Read file contents
        $fileContents = file_get_contents($mediaPath);
        $mimeType = mime_content_type($mediaPath) ?: 'image/jpeg';

        $response = $client->post('media/upload.json', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$accessToken,
            ],
            RequestOptions::MULTIPART => [
                [
                    'name' => 'media',
                    'contents' => $fileContents,
                    'filename' => basename($mediaPath),
                    'headers' => [
                        'Content-Type' => $mimeType,
                    ],
                ],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $mediaIdString = $data['media_id_string'] ?? $data['media_id'] ?? null;

        if (! $mediaIdString) {
            throw new Exception('Failed to get media ID from X API response: '.json_encode($data));
        }

        return (string) $mediaIdString;
    }
}
