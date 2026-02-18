<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $articleModel = config('northcloud.models.article');

        $query = $articleModel::query()
            ->with(['newsSource', 'tags'])
            ->published();

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        if ($tag = $request->get('tag')) {
            $query->withTag($tag);
        }

        $articles = $query->latest('published_at')->paginate(15)->withQueryString();

        return Inertia::render('Articles/Index', [
            'articles' => $articles,
            'queryParams' => $request->only(['search', 'tag']),
        ]);
    }

    public function show(string $slug): Response
    {
        $articleModel = config('northcloud.models.article');

        $article = $articleModel::query()
            ->published()
            ->where('slug', $slug)
            ->with(['newsSource', 'tags', 'articleable'])
            ->firstOrFail();

        $article->incrementViewCount();

        return Inertia::render('Articles/Show', [
            'article' => $article,
        ]);
    }
}
