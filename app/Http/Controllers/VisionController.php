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
        return view('vision.index', compact('user'));
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
            $filename = 'fridge_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/vision_uploads', $filename);
            $imagePath = storage_path('app/' . $path);
            $mimeType = $file->getMimeType();
            $imageUrl = asset('storage/vision_uploads/' . $filename);

            // Coba upload ke Supabase Storage jika aktif
            if ($this->supabaseService->isConfigured()) {
                $uploadRes = $this->supabaseService->uploadPhotoToStorage($imagePath, $filename, $mimeType);
                if ($uploadRes['success']) {
                    $imageUrl = $uploadRes['url'];
                }
            }
        } elseif ($request->filled('sample')) {
            // Sampel foto demo kulkas untuk presentasi sidang
            $sampleKey = $request->sample;
            $samples = [
                'fridge_1' => [
                    'url' => 'https://images.unsplash.com/photo-1584269600464-37b1b58a9fe7?auto=format&fit=crop&w=800&q=80',
                    'name' => 'Kulkas Sayur & Telur Segar'
                ],
                'fridge_2' => [
                    'url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=800&q=80',
                    'name' => 'Keranjang Belanjaan Pasar Tradisional'
                ],
                'fridge_3' => [
                    'url' => 'https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?auto=format&fit=crop&w=800&q=80',
                    'name' => 'Bahan Dapur & Bumbu Masak'
                ]
            ];

            $selected = $samples[$sampleKey] ?? $samples['fridge_1'];
            $imageUrl = $selected['url'];

            // Download sampel lokal temporer untuk analisis vision
            $tempPath = storage_path('app/public/vision_uploads/sample_' . $sampleKey . '.jpg');
            if (!file_exists($tempPath)) {
                try {
                    $content = file_get_contents($imageUrl);
                    file_put_contents($tempPath, $content);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $imagePath = file_exists($tempPath) ? $tempPath : $imageUrl;
        } elseif ($request->filled('image_base64')) {
            $imagePath = $request->image_base64;
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

