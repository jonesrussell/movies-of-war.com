<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\MarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Review
 */
class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $renderer = app(MarkdownRenderer::class);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'movie_id' => $this->movie_id,
            'rating' => (float) $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'content_html' => $renderer->toHtml($this->content),
            'content_excerpt' => $renderer->toExcerpt($this->content, 200),
            'has_spoilers' => $this->has_spoilers,
            'is_published' => $this->is_published,
            'helpful_count' => $this->helpful_count,
            'comments_count' => $this->comments_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'stars' => $this->stars,
            'is_edited' => $this->is_edited,
            'formatted_date' => $this->created_at->diffForHumans(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'movie' => $this->whenLoaded('movie', fn () => [
                'id' => $this->movie->id,
                'slug' => $this->movie->slug,
                'title' => $this->movie->title,
                'poster_url' => $this->movie->poster_url ?? null,
            ]),
            'can_edit' => $user ? $user->can('update', $this->resource) : false,
            'can_delete' => $user ? $user->can('delete', $this->resource) : false,
            'comments' => $this->whenLoaded('comments', fn () => ReviewCommentResource::collection($this->comments)->resolve($request)),
        ];
    }
}
