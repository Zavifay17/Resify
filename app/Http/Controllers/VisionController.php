<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Services\InventoryService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VisionController extends Controller
{
    protected GeminiService $geminiService;
    protected InventoryService $inventoryService;
    protected SupabaseService $supabaseService;

    public function __construct(
        GeminiService $geminiService,
        InventoryService $inventoryService,
        SupabaseService $supabaseService
    ) {
        $this->geminiService = $geminiService;
        $this->inventoryService = $inventoryService;
        $this->supabaseService = $supabaseService;
    }

    public function index()
    {
        $user = Auth::user();

        return view('vision.index', [
            'user' => $user,
            'sampel' => $this->daftarSampel(),
        ]);
    }

    /**
     * Foto contoh berlisensi bebas yang disimpan lokal (public/images/samples)
     supaya tombol sampel dan foto yang dikirim ke AI selalu gambar yang sama.
     */
    private function daftarSampel(): array
    {
        $daftar = [
            'kulkas' => ['label' => 'Kulkas Sayur', 'file' => 'kulkas-sayur.jpg', 'ket' => 'Sayuran & telur di dalam kulkas'],
            'pasar' => ['label' => 'Belanja Pasar', 'file' => 'belanja-pasar.jpg', 'ket' => 'Belanjaan pasar tradisional'],
            'bumbu' => ['label' => 'Bumbu Dapur', 'file' => 'bumbu-dapur.jpg', 'ket' => 'Bumbu & rempah masakan'],
            'sachet' => ['label' => 'Bumbu Sachet', 'file' => 'bumbu-sachet.jpg', 'ket' => 'Bumbu instan kemasan sachet'],
        ];

        foreach ($daftar as $key => $item) {
            $daftar[$key]['path'] = public_path('images/samples/' . $item['file']);
            $daftar[$key]['url'] = asset('images/samples/' . $item['file']);
            $daftar[$key]['key'] = $key;
        }

        return $daftar;
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'image' => 'nullable|image|max:10240', // max 10MB
            'sample' => 'nullable|string',
            'image_base64' => 'nullable|string',
        ]);

        $imagePath = null;
        $mimeType = 'image/jpeg';
        $imageUrl = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = strtolower($file->guessExtension() ?: 'jpg');
            $filename = 'fridge_' . bin2hex(random_bytes(6)) . '.' . $ext;

            // Disk public sudah berakar di storage/app/public, jadi folder cukup "vision_uploads"
            $path = $file->storeAs('vision_uploads', $filename, 'public');
            if (!$path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto gagal disimpan di server. Periksa izin tulis folder storage lalu ulangi.'
                ], 500);
            }

            $imagePath = Storage::disk('public')->path($path);
            $mimeType = $file->getMimeType() ?: 'image/jpeg';
            $imageUrl = asset('storage/' . $path);

            // Coba upload ke Supabase Storage jika aktif
            if ($this->supabaseService->isConfigured()) {
                $uploadRes = $this->supabaseService->uploadPhotoToStorage($imagePath, $filename, $mimeType);
                if ($uploadRes['success']) {
                    $imageUrl = $uploadRes['url'];
                }
            }
        } elseif ($request->filled('sample')) {
            // Sampel foto demo untuk presentasi: file lokal, tidak perlu unduh saat runtime
            $sampel = $this->daftarSampel()[$request->sample] ?? null;

            if (!$sampel || !is_file($sampel['path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto contoh "' . ($sampel['label'] ?? $request->sample) . '" tidak ditemukan di server.'
                ], 422);
            }

            $imagePath = $sampel['path'];
            $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
            $imageUrl = $sampel['url'];
        } elseif ($request->filled('image_base64')) {
            $imagePath = $request->image_base64;
            // Jika dikirim berupa base64 data URI dari capture kamera langsung
            if (str_contains($request->image_base64, ';base64,')) {
                try {
                    $parts = explode(';base64,', $request->image_base64);
                    $rawHeader = $parts[0];
                    $mimeType = str_replace('data:', '', $rawHeader) ?: 'image/jpeg';
                    $decoded = base64_decode($parts[1]);
                    $ext = str_contains($mimeType, 'png') ? 'png' : 'jpg';
                    $filename = 'capture_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    Storage::disk('public')->put('vision_uploads/' . $filename, $decoded);
                    $imagePath = Storage::disk('public')->path('vision_uploads/' . $filename);
                    $imageUrl = asset('storage/vision_uploads/' . $filename);
                } catch (\Throwable $e) {
                    // Analisis tetap berjalan memakai data URI aslinya
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Silakan unggah foto kulkas atau pilih salah satu sampel gambar pengujian.'
            ], 422);
        }

        // Panggil Gemini Vision API
        $result = $this->geminiService->analyzeFridgeVision($imagePath, $mimeType);

        return response()->json(array_merge($result, [
            'image_url' => $imageUrl
        ]));
    }

    public function saveBatch(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.category' => 'required|string',
            'items.*.estimated_quantity' => 'required|numeric',
            'items.*.unit' => 'required|string',
            'items.*.typical_shelf_life_days' => 'nullable|integer',
            'items.*.storage_location' => 'nullable|string',
            'items.*.freshness_notes' => 'nullable|string',
        ]);

        $importedCount = $this->inventoryService->importVisionBatch($user->id, $validated['items']);

        return redirect()->route('inventory.index')
            ->with('success', "Hebat! Sebanyak {$importedCount} bahan makanan dari foto berhasil diidentifikasi dan ditambahkan ke inventaris dapur Anda.");
    }
}

