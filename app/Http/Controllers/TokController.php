<?php

namespace App\Http\Controllers;

use App\Models\Tok;
use App\Models\TokCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TokController extends Controller
{
    public function index(Request $request)
    {
        $toks = Tok::with(['user:id,name', 'category:id,title'])
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get(['id','title','user_id','tok_category_id']);

        $categories = TokCategory::orderBy('title')->get(['id','title']);

        return Inertia::render('Toks/Index', [
            'toks' => $toks,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required','string','max:255'],
            'tok_category_id' => ['nullable','exists:tok_categories,id'],
        ]);

        $request->user()->toks()->create($validated);

        return redirect()->route('toks.index');
    }

    public function update(Request $request, Tok $tok)
    {
        if ($request->user()->id !== $tok->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required','string','max:255'],
            'tok_category_id' => ['nullable','exists:tok_categories,id'],
        ]);

        $tok->update($validated);

        return redirect()->route('toks.index');
    }

    public function destroy(Request $request, Tok $tok)
    {
        if ($request->user()->id !== $tok->user_id) {
            abort(403);
        }
        $tok->delete();
        return redirect()->route('toks.index');
    }

    /**
     * Search toks by title. If "all" query param is truthy, search across all users,
     * otherwise restrict to the authenticated user's toks.
     * Returns JSON with matching toks and a distinct list of users who have matches.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'all' => ['nullable'],
        ]);

        $q = (string) $request->input('q', '');
        $all = filter_var($request->input('all'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        $builder = Tok::query()
            ->with(['user:id,name', 'category:id,title'])
            ->select(['id', 'title', 'user_id', 'tok_category_id'])
            ->latest('id');

        if (!$all) {
            $builder->where('user_id', $request->user()->id);
        }

        if ($q !== '') {
            $builder->where('title', 'like', "%{$q}%");
        }

        $toks = $builder->limit(100)->get();

        // Aggregate users with counts of matching toks
        $userCounts = Tok::query()
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->when(!$all, function ($q2) use ($request) {
                $q2->where('user_id', $request->user()->id);
            })
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where('title', 'like', "%{$q}%");
            })
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->limit(50)
            ->get();

        $userIds = $userCounts->pluck('user_id')->all();
        $users = \App\Models\User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $matchingUsers = $userCounts->map(function ($row) use ($users) {
            $u = $users->get($row->user_id);
            return [
                'id' => $row->user_id,
                'name' => $u?->name,
                'count' => (int) $row->cnt,
            ];
        })->values();

        return response()->json([
            'toks' => $toks,
            'users' => $matchingUsers,
        ]);
    }
}
