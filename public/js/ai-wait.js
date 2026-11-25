/**
 * Penghitung waktu tunggu AI yang mengikuti kejadian sebenarnya:
 * jam berjalan, bilah progres, langkah, dan titik pada tombol.
 */
(function () {
    const pemantau = new Map();

    function angka(ms) {
        return (ms / 1000).toFixed(1).replace('.', ',');
    }

    function jalankan(id, opsi) {
        const root = document.querySelector('[data-ai-wait="' + id + '"]');
        // root boleh tidak ada: sebagian tombol hanya butuh titik dan jam berjalan, tanpa kartu animator.
        const wilayah = root || document;

        hentikan(id);

        const pilihan = opsi || {};
        const estimasi = Math.max(2500, Number(pilihan.estimasi) || 9000);
        const langkah = Array.from(wilayah.querySelectorAll('[data-ai-langkah]'));
        const bilah = wilayah.querySelector('[data-ai-bar]');
        const jam = wilayah.querySelector('[data-ai-jam]');
        const fase = wilayah.querySelector('[data-ai-fase]');
        // Langkah terakhir sengaja dibiarkan menyala sampai jawaban benar-benar tiba.
        const plafon = langkah.length ? Math.max(0.4, langkah.length / (langkah.length + 1) - 0.02) : 0.9;
        const konstantaWaktu = Math.max(4000, estimasi * 0.9);
        let titik = [];
        const mulai = performance.now();

        let kunci = 0;
        let status = 'jalan';
        let jamDetik = null;

        if (root) {
            root.classList.add('is-real');
            root.classList.remove('is-selesai', 'is-gagal');
        }
        if (fase) fase.textContent = pilihan.fase || 'Menyiapkan permintaan ke Gemini...';

        function ambilTitik() {
            // Tombol kadang baru muncul setelah Alpine menggambar ulang, jadi ditanya ulang tiap ketukan.
            titik = Array.from(document.querySelectorAll('[data-ai-titik="' + id + '"]')).map((wadah) => {
                wadah.classList.add('is-real');
                return Array.from(wadah.querySelectorAll('i'));
            }).filter((daftar) => daftar.length > 0);
        }

        function gambar() {
            const detik = performance.now() - mulai;
            const progresBebas = status === 'jalan'
                ? plafon * (1 - Math.exp(-detik / konstantaWaktu))
                : 1;
            const p = status === 'jalan'
                ? Math.min(plafon, Math.max(kunci, progresBebas))
                : Math.min(1, Math.max(kunci, progresBebas));

            if (bilah) bilah.style.setProperty('--p', (p * 100).toFixed(1));

            langkah.forEach((li, index) => {
                const mulaiLangkah = index / (langkah.length + 1);
                const akhirLangkah = (index + 1) / (langkah.length + 1);
                li.classList.toggle('is-done', p >= akhirLangkah);
                li.classList.toggle('is-aktif', status === 'jalan' && p >= mulaiLangkah && p < akhirLangkah);
            });

            if (!titik.length) ambilTitik();
            titik.forEach((daftar) => {
                const aktif = Math.floor(detik / 380) % daftar.length;
                daftar.forEach((dot, index) => dot.classList.toggle('is-nyala', index === aktif));
            });

            if (jam) {
                const label = status === 'gagal' ? 'Berhenti di ' : (status === 'sukses' ? 'Selesai dalam ' : 'Berjalan ');
                jam.textContent = ' · ' + label + angka(detik) + ' detik';
            }

            if (status !== 'jalan') {
                clearInterval(jamDetik);
                jamDetik = null;
            }
        }

        pemantau.set(id, {
            geser(batas, teksFase) {
                if (status !== 'jalan') return;
                kunci = Math.max(kunci, Number(batas) || 0);
                if (fase && teksFase) fase.textContent = teksFase;
                gambar();
            },
            hentikan(baru) {
                status = baru || 'sukses';
                if (root) {
                    root.classList.toggle('is-gagal', status === 'gagal');
                    root.classList.toggle('is-selesai', status === 'sukses');
                }
                gambar();
            }
        });

        jamDetik = setInterval(gambar, 120);
        gambar();
    }

    function pemantauDari(id) {
        return pemantau.get(id) || null;
    }

    function hentikan(id) {
        const aktif = pemantau.get(id);
        if (!aktif) return;
        aktif.hentikan('sukses');
        pemantau.delete(id);
    }

    window.AiTunggu = {
        jalankan: jalankan,
        geser(id, batas, teksFase) {
            const aktif = pemantauDari(id);
            if (aktif) aktif.geser(batas, teksFase);
        },
        selesai(id) {
            const aktif = pemantauDari(id);
            if (!aktif) return;
            aktif.hentikan('sukses');
            pemantau.delete(id);
        },
        gagal(id) {
            const aktif = pemantauDari(id);
            if (!aktif) return;
            aktif.hentikan('gagal');
            pemantau.delete(id);
        }
    };
})();
