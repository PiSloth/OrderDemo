<?php

namespace App\Http\Controllers\Api\Psi;

use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class HashTagController extends Controller
{
    public function __invoke(Request $request): Collection
    {
        return Hashtag::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->when(
                $request->search,
                fn(Builder $query) => $query
                    ->where('name', 'like', "%{$request->search}%")

            )
            ->when(
                $request->exists('selected'),
                fn(Builder $query) => $query->whereIn('id', $request->input('selected', [])),
                fn(Builder $query) => $query->limit(10)
            )
            ->get();
    }
}
