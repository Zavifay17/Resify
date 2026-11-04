<?php

namespace App\Http\Controllers;

use App\Models\AiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = AiLog::query();

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action_type', $request->action);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $logs = $query->latest()->paginate(15)->withQueryString();

        // Statistik Analitik Token & Performa
        $totalTokens = AiLog::sum('total_tokens') ?: 0;
        $promptTokens = AiLog::sum('prompt_tokens') ?: 0;
        $responseTokens = AiLog::sum('response_tokens') ?: 0;
        $avgLatency = (int) round(AiLog::avg('execution_time_ms') ?: 0);
        $totalHits = AiLog::count() ?: 1;
        $successCount = AiLog::where('status', 'success')->count();
        $successRate = round(($successCount / $totalHits) * 100, 1);

        // Penghematan kuota berkat Caching System (setiap hit cache menghemat ~500 token)
        $cachedRecipesCount = \App\Models\RecipeCache::count();
        $savedTokens = $cachedRecipesCount * 450;

        return view('logs.index', compact(
            'logs',
            'totalTokens',
            'promptTokens',
            'responseTokens',
            'avgLatency',
            'totalHits',
            'successRate',
            'savedTokens'
        ));
    }

    public function show($id)
    {
        $log = AiLog::findOrFail($id);
        return response()->json($log);
    }
}

