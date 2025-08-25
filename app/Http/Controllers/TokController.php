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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = (string) $request->input('q', '');
        // robust boolean handling: treat '1', 'true', 'on' as true; '0', 'false', 'off' as false
        $rawAll = strtolower((string) $request->input('all', ''));
        $explicitFalse = in_array($rawAll, ['0','false','off','no'], true);
        $explicitTrue = in_array($rawAll, ['1','true','on','yes'], true);
        // Default behavior: if there's a query and no explicit false, search globally
        $all = $explicitTrue || ($q !== '' && !$explicitFalse);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage < 1) { $perPage = 20; }
        if ($perPage > 100) { $perPage = 100; }

        $builder = Tok::query()
            ->with(['user:id,name', 'category:id,title'])
            ->select(['id', 'title', 'user_id', 'tok_category_id'])
            ->latest('id');

        if (!$all) {
            $builder->where('user_id', $request->user()->id);
        }

        if ($q !== '') {
            $builder->where(function($qb) use ($q) {
                $qb->where('title', 'like', "%{$q}%")
                   ->orWhereHas('user', function($uq) use ($q) {
                       $uq->where('name', 'like', "%{$q}%");
                   })
                   ->orWhereHas('category', function($cq) use ($q) {
                       $cq->where('title', 'like', "%{$q}%");
                   });
            });
        }

        $paginator = $builder->paginate($perPage, ['*'], 'page', $page);
        $toks = $paginator->items();

        // Aggregate users with counts of matching toks
        $userCounts = Tok::query()
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->when(!$all, function ($q2) use ($request) {
                $q2->where('user_id', $request->user()->id);
            })
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where(function($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%");
                });
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
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
