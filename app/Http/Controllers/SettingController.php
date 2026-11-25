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

        $geminiApiKey = $this->geminiService->keyTerpakai();
        $geminiKeyDatabaseSalah = $this->geminiService->keyDatabase() === ''
            && trim((string) SystemSetting::get('gemini_api_key', '')) !== '';
        $geminiModel = SystemSetting::get('gemini_model', env('GEMINI_MODEL', 'gemini-3-flash-preview'));

        $supabaseUrl = SystemSetting::get('supabase_url', env('SUPABASE_URL', ''));
        $supabaseKey = SystemSetting::get('supabase_key', env('SUPABASE_KEY', ''));
        $supabaseBucket = SystemSetting::get('supabase_bucket', env('SUPABASE_BUCKET', 'ingredients'));

        $setupSql = file_exists(base_path('supabase/sql/setup.sql')) ? file_get_contents(base_path('supabase/sql/setup.sql')) : '';
        $supabaseRef = '';
        if (preg_match('#https://([a-z0-9_-]+)\.supabase\.co#i', $supabaseUrl, $matches)) {
            $supabaseRef = $matches[1];
        }

        $studentName = $user->name;
        $perluToken = !$this->supabaseService->punyaTokenKelola();

        $authGoogle = app(AuthController::class);
        $googleSiap = $authGoogle->googleSiap();
        $googleCallback = (string) (config('services.google.redirect') ?: url('/auth/google/callback'));
        $googlePerluDiperbaiki = $authGoogle->kredensialGooglePerluDiperbaiki();

        return view('settings.index', compact(
            'user',
            'geminiApiKey',
            'geminiKeyDatabaseSalah',
            'geminiModel',
            'supabaseUrl',
            'supabaseKey',
            'supabaseBucket',
            'setupSql',
            'supabaseRef',
            'studentName',
            'perluToken',
            'googleSiap',
            'googleCallback',
            'googlePerluDiperbaiki'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'gemini_api_key' => ['nullable', 'string', 'min:30', 'max:255', 'regex:/^[A-Za-z0-9_\-\.]+$/'],
            'gemini_model' => 'required|string',
            'supabase_url' => 'nullable|string',
            'supabase_key' => 'nullable|string',
            'supabase_bucket' => 'nullable|string',
            'google_client_id' => ['nullable', 'string', 'max:200', 'regex:/^\d{6,20}-[a-z0-9]{10,60}\.apps\.googleusercontent\.com$/'],
            'google_client_secret' => ['nullable', 'string', 'max:200', 'regex:/^GOCSPX-[A-Za-z0-9_\-]{16,80}$/'],
        ], [
            'gemini_api_key.min' => 'Gemini API Key terlalu pendek. Salin kunci utuh dari Google AI Studio (minimal 30 karakter, tanpa spasi).',
            'gemini_api_key.regex' => 'Gemini API Key hanya boleh berisi huruf, angka, tanda hubung, garis bawah, dan titik. Jangan menuliskan nama atau kata sandi di kolom ini.',
            'google_client_id.regex' => 'OAuth Client ID Google berbentuk angka-karakter.apps.googleusercontent.com, bukan alamat email. Salin Client ID di Google Cloud > Google Auth Platform > Clients.',
            'google_client_secret.regex' => 'OAuth Client Secret Google selalu diawali GOCSPX- dan tanpa spasi. Salin secret dari client yang sama dengan Client ID di atas.',
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

        // Disimpan di database supaya login Google langsung aktif tanpa menulis ulang .env.
        if (!empty($validated['google_client_id'])) {
            SystemSetting::set('google_client_id', trim($validated['google_client_id']), 'Google OAuth Client ID');
        }
        if (!empty($validated['google_client_secret'])) {
            SystemSetting::set('google_client_secret', trim($validated['google_client_secret']), 'Google OAuth Client Secret');
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

    /**
     * Status tabel cloud (sudah terbentuk atau belum) untuk kartu Skema & ERD.
     */
    public function tabelCloud()
    {
        return response()->json([
            'configured' => $this->supabaseService->isConfigured(),
            'perlu_token' => !$this->supabaseService->punyaTokenKelola(),
            'tabel' => $this->supabaseService->daftarTabelCloud(),
        ]);
    }

    /**
     * Jalankan supabase/sql/setup.sql dari aplikasi (butuh SUPABASE_ACCESS_TOKEN).
     */
    public function bangunSkema()
    {
        $result = $this->supabaseService->bangunSkema();

        return response()->json($result);
    }

    /**
     * Kirim seluruh data lokal ke cloud supaya ERD tidak hanya berisi kerangka.
     */
    public function sinkronkanData()
    {
        $result = $this->supabaseService->sinkronkanSemuaData();

        return response()->json($result);
    }
}

