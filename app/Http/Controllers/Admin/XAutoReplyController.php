<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\XAutoReplyRule;
use App\Services\XAutoReplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class XAutoReplyController extends Controller
{
    public function __construct(
        protected XAutoReplyService $autoReplyService
    ) {}

    /**
     * Display all auto-reply rules.
     */
    public function index(): Response
    {
        $rules = XAutoReplyRule::query()
            ->orderedByPriority()
            ->paginate(20);

        return Inertia::render('Admin/XAutoReplies/Index', [
            'rules' => $rules,
        ]);
    }

    /**
     * Store a new auto-reply rule.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_keywords' => ['required', 'array', 'min:1'],
            'trigger_keywords.*' => ['required', 'string'],
            'trigger_type' => ['required', 'in:mention,hashtag,keyword'],
            'reply_template' => ['required', 'string', 'max:280'],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        XAutoReplyRule::create($validated);

        return redirect()->route('admin.x-auto-replies.index')
            ->with('success', 'Auto-reply rule created.');
    }

    /**
     * Update an auto-reply rule.
     */
    public function update(Request $request, XAutoReplyRule $rule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'trigger_keywords' => ['sometimes', 'required', 'array', 'min:1'],
            'trigger_keywords.*' => ['required', 'string'],
            'trigger_type' => ['sometimes', 'required', 'in:mention,hashtag,keyword'],
            'reply_template' => ['sometimes', 'required', 'string', 'max:280'],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        $rule->update($validated);

        return redirect()->route('admin.x-auto-replies.index')
            ->with('success', 'Auto-reply rule updated.');
    }

    /**
     * Remove an auto-reply rule.
     */
    public function destroy(XAutoReplyRule $rule): RedirectResponse
    {
        $rule->delete();

        return redirect()->route('admin.x-auto-replies.index')
            ->with('success', 'Auto-reply rule deleted.');
    }

    /**
     * Toggle active status of a rule.
     */
    public function toggle(XAutoReplyRule $rule): RedirectResponse
    {
        $rule->update(['is_active' => ! $rule->is_active]);

        return redirect()->route('admin.x-auto-replies.index')
            ->with('success', 'Auto-reply rule status updated.');
    }

    /**
     * Test rule matching.
     */
    public function test(Request $request, XAutoReplyRule $rule): Response
    {
        $text = $request->get('text', '');
        $matches = $this->autoReplyService->testRule($rule, $text);

        return Inertia::render('Admin/XAutoReplies/Test', [
            'rule' => $rule,
            'testText' => $text,
            'matches' => $matches,
            'generatedReply' => $matches ? $rule->generateReply() : null,
        ]);
    }
}
