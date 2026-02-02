<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MarkdownRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MarkdownPreviewController extends Controller
{
    /**
     * Max length for preview (matches typical text column; avoid huge payloads).
     */
    private const MAX_MARKDOWN_LENGTH = 50_000;

    public function __invoke(Request $request, MarkdownRenderer $renderer): JsonResponse
    {
        $validated = $request->validate([
            'markdown' => ['required', 'string', 'max:'.self::MAX_MARKDOWN_LENGTH],
        ]);

        $html = $renderer->toHtml($validated['markdown']);

        return response()->json(['html' => $html]);
    }
}
