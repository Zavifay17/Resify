<?php

namespace App\Http\Controllers;

use App\Models\RecipeSubstitution;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubstituteController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function suggest(Request $request)
    {
        $request->validate([
            'ingredient' => 'required|string|max:150',
            'recipe_context' => 'nullable|string|max:255',
            'recipe_id' => 'nullable|exists:recipes_cache,id',
        ]);

        $ingredient = trim($request->ingredient);
        $context = $request->recipe_context ?? 'Masakan Rumahan Indonesia';

        $result = $this->geminiService->suggestSubstitute($ingredient, $context);

        if ($result['success'] && !empty($result['data']['substitutes'])) {
            $user = Auth::user();
            $firstSub = $result['data']['substitutes'][0] ?? null;

            if ($firstSub) {
                RecipeSubstitution::create([
                    'user_id' => $user?->id,
                    'recipe_id' => $request->recipe_id,
                    'original_ingredient' => $ingredient,
                    'substitute_ingredient' => $firstSub['substitute_name'] ?? 'Bahan Alternatif',
                    'ratio_adjustment' => $firstSub['ratio'] ?? '1:1',
                    'culinary_notes' => ($firstSub['taste_profile'] ?? '') . '. ' . ($firstSub['culinary_notes'] ?? ''),
                ]);
            }
        }

        return response()->json($result);
    }
}

