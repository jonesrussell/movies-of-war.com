<?php

namespace App\Services;

use App\Models\XAutoReplyRule;
use Exception;
use Illuminate\Support\Facades\Log;

class XAutoReplyService
{
    public function __construct(
        protected XApiService $xApiService
    ) {}

    /**
     * Process mentions and send auto-replies based on rules.
     *
     * @param  int  $limit  Maximum mentions to process
     * @return int Number of replies sent
     */
    public function processMentions(int $limit = 10): int
    {
        try {
            // Get recent mentions
            $mentionsResponse = $this->xApiService->getMentions(['max_results' => $limit]);
            $mentions = $mentionsResponse['data'] ?? [];

            if (empty($mentions)) {
                return 0;
            }

            // Get active rules ordered by priority
            $rules = XAutoReplyRule::active()->orderedByPriority()->get();

            if ($rules->isEmpty()) {
                return 0;
            }

            $repliesSent = 0;

            foreach ($mentions as $mention) {
                $text = strtolower($mention['text'] ?? '');

                // Find matching rule
                foreach ($rules as $rule) {
                    if ($rule->matches($text)) {
                        try {
                            $this->checkAndReply($mention, $rule);
                            $repliesSent++;
                            break; // Only reply once per mention
                        } catch (Exception $e) {
                            Log::warning('X Auto Reply: Failed to send reply', [
                                'tweet_id' => $mention['id'] ?? null,
                                'rule_id' => $rule->id,
                                'error' => $e->getMessage(),
                            ]);

                            // Continue with next rule/mention
                        }
                    }
                }

                // Rate limiting: sleep between processing
                usleep(1000000); // 1 second between replies
            }

            return $repliesSent;
        } catch (Exception $e) {
            Log::error('X Auto Reply: Failed to process mentions', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if a mention matches a rule and send reply.
     *
     * @param  array  $mention  Mention tweet data from API
     * @param  XAutoReplyRule  $rule  Auto-reply rule
     * @return bool Success
     *
     * @throws Exception
     */
    public function checkAndReply(array $mention, XAutoReplyRule $rule): bool
    {
        $tweetId = $mention['id'] ?? null;

        if (! $tweetId) {
            throw new Exception('Invalid mention data: missing tweet ID');
        }

        $context = [
            'author' => $mention['author_id'] ?? 'user',
            'tweet_id' => $tweetId,
        ];

        $replyContent = $rule->generateReply($context);

        return $this->sendReply($tweetId, $replyContent);
    }

    /**
     * Send a reply tweet.
     *
     * @param  string  $tweetId  The tweet ID to reply to
     * @param  string  $content  Reply content
     * @return bool Success
     *
     * @throws Exception
     */
    public function sendReply(string $tweetId, string $content): bool
    {
        try {
            // Post reply using XApiService
            $this->xApiService->postTweet($content, null, $tweetId);

            Log::info('X Auto Reply: Reply sent', [
                'reply_to_tweet_id' => $tweetId,
                'content_length' => strlen($content),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('X Auto Reply: Failed to send reply', [
                'reply_to_tweet_id' => $tweetId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Test if a text matches a rule.
     */
    public function testRule(XAutoReplyRule $rule, string $text): bool
    {
        return $rule->matches($text);
    }
}
