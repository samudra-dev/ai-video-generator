# Video Walkthrough Script — AI Video Generator

**Target Duration:** 3–5 menit
**Bahasa:** Indonesia (atau English, bebas)

---

## Scene 1: Intro (~20 detik)

**[Tampilkan: Halaman login di browser]**

> "Halo, perkenalkan saya Samudra Zulqifli. Di video ini saya akan menjelaskan project AI Video Generator yang saya kerjakan untuk task assessment. Ini adalah aplikasi web berbasis Laravel yang memungkinkan user generate video script dan scene breakdown menggunakan AI. Aplikasinya sudah live di Railway, link-nya ada di deskripsi."

---

## Scene 2: Tech Stack Overview (~30 detik)

**[Tampilkan: VS Code dengan struktur folder, atau slide singkat]**

> "Stack yang saya pakai:
>
> - **Laravel 13** dengan PHP 8.4 sebagai backend
> - **Blade templates** untuk view, dengan **Tailwind CSS** dan **Alpine.js** via CDN — jadi tidak perlu build step terpisah
> - Database **SQLite** untuk lokal, **MySQL** untuk production
> - AI provider pakai **OpenRouter API** dengan model **Gemini Flash 2.0** yang free tier
> - Deploy di **Railway**"

---

## Scene 3: Demo — Authentication (~30 detik)

**[Tampilkan: Register page → buat akun baru → otomatis login → dashboard]**

> "Mari kita mulai dari authentication. User bisa register dengan nama, email, dan password. Setelah register, otomatis login dan masuk ke dashboard."

**[Tampilkan: Dashboard dengan stats]**

> "Di dashboard ada statistik total generations, completed, dan failed, plus list 4 generations terbaru."

---

## Scene 4: Demo — Generate Video Script (~60 detik)

**[Tampilkan: Klik 'Generate Video Script' → masuk ke form]**

> "Sekarang masuk ke fitur utama. Di form generate ini ada beberapa input: Video Type, Tone, Topic, Keywords, Target Audience, dan optional Duration."

**[Tampilkan: Klik salah satu Quick Template di sidebar]**

> "Saya juga implement fitur **Content Templates** sebagai bonus — ada 5 template seperti Product Launch, Educational Tutorial, Social Media Reel, dan lain-lain. Klik salah satu, form auto-fill."

**[Tampilkan: Submit form, loading spinner]**

> "Klik Generate, tombol berubah jadi loading spinner. Sistem mengirim prompt ke OpenRouter API yang membutuhkan waktu sekitar 10-30 detik."

**[Tampilkan: Halaman detail dengan tabs Script + Scene Breakdown]**

> "Setelah selesai, user diarahkan ke halaman detail. Ada dua tab: **Full Script** yang berisi narasi lengkap, dan **Scene Breakdown** yang menampilkan setiap scene dengan nomor, durasi, narasi, dan visual direction."

---

## Scene 5: Demo — Export & History (~40 detik)

**[Tampilkan: Klik tombol Export .txt]**

> "User bisa export script sebagai file .txt — formatnya rapi dengan header, full script, dan scene-by-scene breakdown."

**[Tampilkan: Buka file .txt yang ter-download]**

> "Ini hasilnya, ready untuk dipakai."

**[Tampilkan: Klik History → list generasi]**

> "Di History, user bisa lihat semua generasi sebelumnya. Ada **search bar** untuk cari berdasarkan judul atau topik, ada pagination, dan setiap entry bisa di-view detail atau di-delete."

---

## Scene 6: Code Walkthrough Singkat (~60 detik)

**[Tampilkan: VS Code — buka file VideoController.php]**

> "Sekilas tentang arsitekturnya. `VideoController` menangani semua action terkait video. Method `store` ini adalah core-nya:
>
> - Validasi input
> - Buat record dengan status `pending`
> - Panggil `OpenRouterService` untuk generate via AI
> - Update record dengan script + scenes, status jadi `completed`
> - Kalau API gagal, status jadi `failed` dan user dapat error message"

**[Tampilkan: Buka OpenRouterService.php]**

> "OpenRouterService meng-encapsulate AI call. Saya gunakan JSON mode dari OpenRouter supaya response selalu structured dan bisa langsung di-parse. Prompt-nya jelas: minta script + scene breakdown dalam format JSON."

**[Tampilkan: Buka migration / model]**

> "Database schema cukup straightforward — tabel `video_generations` dengan foreign key ke users, kolom untuk semua input form, plus `script` dan `scenes` (JSON) untuk hasil AI."

---

## Scene 7: Testing & Deployment (~30 detik)

**[Tampilkan: Terminal — jalankan `php artisan test`]**

> "Saya juga tulis test suite — ada 16 feature test mencakup auth, video generation, search, delete, dan export. Semua pass."

**[Tampilkan: Railway dashboard atau live URL di browser]**

> "Deployment di Railway — push ke GitHub, Railway auto-detect Laravel, MySQL service di-attach, environment variables di-set, dan auto-deploy on every push."

---

## Scene 8: Closing (~15 detik)

**[Tampilkan: Live URL homepage]**

> "Itu dia walkthrough singkat AI Video Generator. Live URL dan dokumentasi lengkap ada di email submission. Terima kasih sudah menonton!"

---

## Tips Recording

- **Latihan dulu sekali** sebelum recording final supaya alur smooth
- **Tutup notifikasi** Mac (Do Not Disturb mode)
- **Resize browser** ke ukuran sedang (jangan terlalu kecil supaya text terbaca)
- **Zoom in** kalau kode terlalu kecil (Cmd + di browser/VS Code)
- **Bicara santai**, anggap ngomong ke teman — jangan kaku baca script
- **Jika salah ngomong**, tinggal pause sebentar lalu ulang kalimat — nanti edit kalau perlu

---

## Upload ke YouTube

1. Buka [youtube.com/upload](https://youtube.com/upload)
2. Pilih file video
3. Title: **"AI Video Generator — Technical Walkthrough"**
4. Description: tempel link Railway + dokumentasi
5. **Visibility: Unlisted** (cukup, tidak perlu public)
6. Save → copy link
7. Tempel link di submission email

---

## Checklist Sebelum Submit

- [ ] Live URL bisa diakses dari browser lain (incognito)
- [ ] Demo akun (kalau perlu) sudah dibuat
- [ ] OpenRouter API key valid (test generate sekali sebelum recording)
- [ ] Documentation PDF / markdown sudah final
- [ ] Video sudah di-upload, link unlisted bisa diakses
- [ ] Email draft siap kirim ke `hrbedaieofficial@gmail.com`
