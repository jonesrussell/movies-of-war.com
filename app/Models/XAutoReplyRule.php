<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XAutoReplyRule extends Model
{
    /** @use HasFactory<\Database\Factories\XAutoReplyRuleFactory> */
    use HasFactory;

    const TRIGGER_MENTION = 'mention';

    const TRIGGER_HASHTAG = 'hashtag';

    const TRIGGER_KEYWORD = 'keyword';

    protected $fillable = [
        'name',
        'trigger_keywords',
        'trigger_type',
        'reply_template',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'trigger_keywords' => 'array',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('id', 'asc');
    }

    public function matches(string $text): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $text = strtolower($text);
        $keywords = array_map('strtolower', $this->trigger_keywords ?? []);

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function generateReply(?array $context = null): string
    {
        $reply = $this->reply_template;

        // Simple variable replacement (can be enhanced)
        if ($context) {
            foreach ($context as $key => $value) {
                $reply = str_replace("{{{$key}}}", $value, $reply);
            }
        }

        return $reply;
    }
}
