<?php

namespace Database\Seeders;

use App\Models\AiLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Mahasiswa BSI Tugas Akhir
        $user = User::updateOrCreate(
            ['email' => 'mahasiswa@bsi.ac.id'],
            [
                'name' => 'Ahmad Fadhilah',
                'password' => Hash::make('password123'),
                'nim' => '12210889',
                'campus' => 'Universitas Bina Sarana Informatika (BSI)',
                'diet_preferences' => ['Halal', 'Tinggi Protein', 'Hemat Waktu'],
                'cooking_skill' => 'Menengah',
                'allergies' => 'Tidak ada alergi'
            ]
        );

        // 2. Default System Settings
        SystemSetting::updateOrCreate(
            ['key' => 'campus_name'],
            ['value' => 'Universitas Bina Sarana Informatika', 'description' => 'Nama Kampus Pengembang']
        );
        SystemSetting::updateOrCreate(
            ['key' => 'student_name'],
            ['value' => 'Ahmad Fadhilah', 'description' => 'Nama Mahasiswa Tugas Akhir']
        );
        SystemSetting::updateOrCreate(
            ['key' => 'student_nim'],
            ['value' => '12210889', 'description' => 'NIM Mahasiswa RPL']
        );
        SystemSetting::updateOrCreate(
            ['key' => 'project_title'],
            ['value' => 'Resepify: Sistem Inventaris Dapur Cerdas & Generator Resep Berbasis AI', 'description' => 'Judul Tugas Akhir']
        );
        SystemSetting::updateOrCreate(
            ['key' => 'gemini_model'],
            ['value' => 'gemini-2.0-flash', 'description' => 'Model Gemini AI Terpilih']
        );

        // 3. Jalankan Seeder Komponen
        $this->call([
            IngredientSeeder::class,
            KitchenInventorySeeder::class,
            RecipeSeeder::class,
        ]);

        // 4. Seeder Riwayat Log AI untuk Evaluasi Dosen
        AiLog::create([
            'user_id' => $user->id,
            'action_type' => 'recipe_generation',
            'model_used' => 'gemini-2.0-flash',
            'prompt_summary' => 'Merancang resep Tumis Kangkung Ayam Filet Saus Tiram dari bahan kulkas',
            'prompt_tokens' => 195,
            'response_tokens' => 340,
            'total_tokens' => 535,
            'execution_time_ms' => 740,
            'status' => 'success',
            'raw_response' => '{"title":"Tumis Kangkung Ayam Filet Saus Tiram","status":"success"}'
        ]);

        AiLog::create([
            'user_id' => $user->id,
            'action_type' => 'vision_detection',
            'model_used' => 'gemini-2.0-flash',
            'prompt_summary' => 'Deteksi Computer Vision bahan masakan kulkas: Telur, Wortel, Kangkung',
            'prompt_tokens' => 310,
            'response_tokens' => 210,
            'total_tokens' => 520,
            'execution_time_ms' => 1120,
            'status' => 'success',
            'raw_response' => '{"detected_items_count":5}'
        ]);

        AiLog::create([
            'user_id' => $user->id,
            'action_type' => 'substitute_suggestion',
            'model_used' => 'gemini-2.0-flash',
            'prompt_summary' => 'Rekomendasi substitusi Bawang Putih dengan Bawang Putih Bubuk & Rasio Takaran',
            'prompt_tokens' => 120,
            'response_tokens' => 180,
            'total_tokens' => 300,
            'execution_time_ms' => 450,
            'status' => 'success',
            'raw_response' => '{"substitute":"Garlic Powder"}'
        ]);
    }
}
