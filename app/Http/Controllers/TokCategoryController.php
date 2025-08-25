<?php

namespace App\Http\Controllers;

use App\Models\TokCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TokCategoryController extends Controller
{
    public function index()
    {
        $categories = TokCategory::orderBy('title')->get(['id','title']);
        return Inertia::render('TokCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required','string','max:255'],
        ]);

        TokCategory::create($validated);
        return redirect()->route('tok-categories.index');
    }

    public function destroy(TokCategory $tokCategory)
    {
        $tokCategory->delete();
        return redirect()->route('tok-categories.index');
    }
}
