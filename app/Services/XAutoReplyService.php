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
            $mentionsResponse = $this->xApiService->getMentionsAsDto(['max_results' => $limit]);

            if ($mentionsResponse->isEmpty()) {
                return 0;
            }

            // Get active rules ordered by priority
            $rules = XAutoReplyRule::active()->orderedByPriority()->get();

            if ($rules->isEmpty()) {
                return 0;
            }

            $repliesSent = 0;

            foreach ($mentionsResponse->tweets as $tweet) {
                $text = strtolower($tweet->text);

                // Find matching rule
                foreach ($rules as $rule) {
                    if ($rule->matches($text)) {
                        try {
                            $this->checkAndReply($tweet, $rule);
                            $repliesSent++;
                            break; // Only reply once per mention
                        } catch (Exception $e) {
                            Log::warning('X Auto Reply: Failed to send reply', [
                                'tweet_id' => $tweet->id,
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
     * @param  \App\Data\X\XTweetData  $tweet  Mention tweet data
     * @param  XAutoReplyRule  $rule  Auto-reply rule
     * @return bool Success
     *
     * @throws Exception
     */
    public function checkAndReply(\App\Data\X\XTweetData $tweet, XAutoReplyRule $rule): bool
    {
        $context = [
            'author' => $tweet->authorId,
            'tweet_id' => $tweet->id,
        ];

        $replyContent = $rule->generateReply($context);

        return $this->sendReply($tweet->id, $replyContent);
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
