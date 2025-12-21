<?php

namespace App\Http\Controllers\Api\Users;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Contracts\Database\Eloquent\Builder;

class Index extends Controller
{
    public function __invoke(Request $request): SupportCollection
    {
        return User::query()
            ->with('department')
            ->select('id', 'name', 'department_id')
            ->where('suspended', false)
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
            ->get()
            ->map(function ($user) {
                $departmentName = $user->department ? $user->department->name : 'No Department';
                return [
                    'id' => $user->id,
                    'name' => $user->name . ' (' . $departmentName . ')',
                ];
            });
    }
}
