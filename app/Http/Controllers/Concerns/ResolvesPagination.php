<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesPagination
{
    /**
     * @param  list<int>  $allowed
     */
    protected function resolvePerPage(Request $request, int $default = 20, array $allowed = [10, 20, 50, 100]): int
    {
        $perPage = (int) $request->input('per_page', $default);

        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }
}
