<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\GeminiService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    protected GeminiService $geminiService;
    protected SupabaseService $supabaseService;

    public function __construct(GeminiService $geminiService, SupabaseService $supabaseService)
    {
        $this->geminiService = $geminiService;
        $this->supabaseService = $supabaseService;
    }

    public function index()
    {
        $user = Auth::user();

        $geminiApiKey = SystemSetting::get('gemini_api_key', env('GEMINI_API_KEY', ''));
        $geminiModel = SystemSetting::get('gemini_model', env('GEMINI_MODEL', 'gemini-3-flash-preview'));

        $supabaseUrl = SystemSetting::get('supabase_url', env('SUPABASE_URL', ''));
        $supabaseKey = SystemSetting::get('supabase_key', env('SUPABASE_KEY', ''));
        $supabaseBucket = SystemSetting::get('supabase_bucket', env('SUPABASE_BUCKET', 'ingredients'));

        $setupSql = file_exists(base_path('supabase/sql/setup.sql')) ? file_get_contents(base_path('supabase/sql/setup.sql')) : '';
        $supabaseRef = '';
        if (preg_match('#https://([a-z0-9_-]+)\.supabase\.co#i', $supabaseUrl, $matches)) {
            $supabaseRef = $matches[1];
        }

        $campusName = SystemSetting::get('campus_name', 'Universitas Bina Sarana Informatika');
        $studentNim = $user->nim ?? SystemSetting::get('student_nim', '12210889');
        $studentName = $user->name;

        return view('settings.index', compact(
            'user',
            'geminiApiKey',
            'geminiModel',
            'supabaseUrl',
            'supabaseKey',
            'supabaseBucket',
            'setupSql',
            'supabaseRef',
            'campusName',
            'studentNim',
            'studentName'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'gemini_api_key' => 'nullable|string',
            'gemini_model' => 'required|string',
            'supabase_url' => 'nullable|string',
            'supabase_key' => 'nullable|string',
            'supabase_bucket' => 'nullable|string',
            'campus_name' => 'nullable|string',
            'student_nim' => 'nullable|string',
        ]);

        if (isset($validated['gemini_api_key'])) {
            SystemSetting::set('gemini_api_key', trim($validated['gemini_api_key']), 'Gemini API Key');
        }
        SystemSetting::set('gemini_model', $validated['gemini_model'], 'Model Gemini AI Terpilih');

        if (isset($validated['supabase_url'])) {
            SystemSetting::set('supabase_url', trim($validated['supabase_url']), 'Supabase Cloud URL');
        }
        if (isset($validated['supabase_key'])) {
            SystemSetting::set('supabase_key', trim($validated['supabase_key']), 'Supabase Cloud API Key');
        }
        if (isset($validated['supabase_bucket'])) {
            SystemSetting::set('supabase_bucket', trim($validated['supabase_bucket']), 'Supabase Storage Bucket');
        }

        if (isset($validated['campus_name'])) {
            SystemSetting::set('campus_name', $validated['campus_name'], 'Nama Kampus');
        }

        $user = Auth::user();
        if ($user && isset($validated['student_nim'])) {
            $user->nim = $validated['student_nim'];
            $user->save();
        }

        return redirect()->route('settings.index')
            ->with('success', 'Konfigurasi API Gemini & Supabase Cloud berhasil disimpan ke database.');
    }

    public function testGemini(Request $request)
    {
        $apiKey = $request->input('api_key');
        $model = $request->input('model', 'gemini-3.5-flash');

        $result = $this->geminiService->testConnection($apiKey, $model);
        return response()->json($result);
    }

    public function testSupabase(Request $request)
    {
        $url = $request->input('url');
        $key = $request->input('key');

        $result = $this->supabaseService->testConnection($url, $key);
        return response()->json($result);
    }
}

