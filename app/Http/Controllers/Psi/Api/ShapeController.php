<?php

namespace App\Http\Controllers\Psi\Api;

use App\Http\Controllers\Controller;
use App\Models\Shape;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ShapeController extends Controller
{
    public function __invoke(Request $request): Collection
    {
        return Shape::query()
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
    /**
     * Display a listing of the resource.
     */
}
