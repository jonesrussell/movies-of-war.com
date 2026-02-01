<?php

use App\Models\XPost;
use Illuminate\Support\Facades\Route;

Route::get('/api/x-feed', function () {
    $posts = XPost::published()
        ->whereNotNull('x_post_id')
        ->orderBy('published_at', 'desc')
        ->limit(5)
        ->get(['id', 'content', 'x_post_id', 'published_at']);

    return response()->json($posts);
})->name('api.x-feed');

Route::get('/admin', function () {
    return redirect()->route('dashboard');
})->middleware('auth');
