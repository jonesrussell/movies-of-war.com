<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\XPostStatus;
use App\Http\Controllers\Controller;
use App\Models\XPost;
use App\Services\XPostSpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class XPostImportController extends Controller
{
    public function __construct(
        private XPostSpreadsheetReader $reader
    ) {}

    /**
     * Show the import form with preview of spreadsheet rows.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        Gate::authorize('create', XPost::class);

        $path = config('x_posts.spreadsheet_path');
        if (! is_string($path) || ! is_readable($path)) {
            return redirect()->route('admin.x-posts.index')
                ->with('error', 'Spreadsheet not found. Set X_POSTS_SPREADSHEET_PATH or place the file at resources/xslx/MoW_X_Posts_2026_CLEANED.xlsx');
        }

        $rows = $this->reader->read($path);

        $preview = array_map(function (array $row) {
            return [
                'row_number' => $row['row_number'],
                'content' => $row['content'],
                'content_preview' => mb_strlen($row['content']) > 80 ? mb_substr($row['content'], 0, 80).'...' : $row['content'],
                'media_urls' => $row['media_urls'],
                'scheduled_for' => $row['scheduled_for'],
                'theme' => $row['theme'],
                'post_type' => $row['post_type'],
            ];
        }, $rows);

        return Inertia::render('Admin/XPosts/Import', [
            'preview' => $preview,
            'maxTweetLength' => XPost::MAX_TWEET_LENGTH,
        ]);
    }

    /**
     * Import selected rows from the spreadsheet as draft X posts.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', XPost::class);

        $validated = $request->validate([
            'row_numbers' => ['required', 'array'],
            'row_numbers.*' => ['integer', 'min: 1'],
        ]);

        $path = config('x_posts.spreadsheet_path');
        if (! is_string($path) || ! is_readable($path)) {
            return redirect()->route('admin.x-posts.index')
                ->with('error', 'Spreadsheet not found.');
        }

        $rows = $this->reader->read($path);
        $byRowNumber = collect($rows)->keyBy('row_number');
        $selected = collect($validated['row_numbers'])->map(fn (int $num) => $byRowNumber->get($num))->filter()->values();

        $imported = 0;
        foreach ($selected as $row) {
            XPost::create([
                'content' => $row['content'],
                'thread_parts' => [],
                'media_urls' => $row['media_urls'],
                'status' => XPostStatus::Draft,
                'scheduled_for' => null,
                'user_id' => $request->user()->id,
            ]);
            $imported++;
        }

        return redirect()->route('admin.x-posts.index')
            ->with('success', $imported === 1 ? '1 post imported as draft.' : "{$imported} posts imported as drafts.");
    }
}
