<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PublicUserController extends Controller
{
    /**
     * Show a user's public toks list by username (uses User.name).
     * URL: /{username}
     */
    public function show(Request $request, string $username)
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage < 1) { $perPage = 20; }
        if ($perPage > 100) { $perPage = 100; }

        // Try exact and case-insensitive match by name first
        $user = User::query()
            ->where('name', $username)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($username)])
            ->first();

        // If not found, try to match by slug of the name (best-effort)
        if (!$user) {
            $candidate = User::query()
                ->select(['id','name'])
                ->get()
                ->first(function ($u) use ($username) {
                    return Str::slug($u->name) === strtolower($username);
                });
            if ($candidate) {
                $user = $candidate;
            }
        }

        if (!$user) {
            abort(404);
        }

        $paginator = $user->toks()
            ->with(['category:id,title'])
            ->select(['id','title','tok_category_id'])
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return Inertia::render('Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'toks' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
