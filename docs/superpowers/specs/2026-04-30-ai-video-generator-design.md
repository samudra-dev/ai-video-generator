# AI Video Generator — Design Spec
Date: 2026-04-30

## Overview
Web application that allows users to generate video scripts and scene breakdowns by providing a topic, keywords, target audience, and desired tone. Built with Laravel 11 + Blade + Alpine.js + Tailwind CSS. AI generation powered by OpenRouter API.

## Stack
- **Backend:** Laravel 11, PHP 8.2
- **Frontend:** Blade templates, Tailwind CSS, Alpine.js
- **Database:** MySQL (Railway)
- **AI:** OpenRouter API — model `google/gemini-flash-1.5` (free tier)
- **Deployment:** Railway

## Architecture

```
app/
  Http/Controllers/
    AuthController.php       — register, login, logout
    VideoController.php      — CRUD + generate trigger
  Models/
    User.php
    VideoGeneration.php
  Services/
    OpenRouterService.php    — AI API call + response parsing
resources/views/
  auth/                      — login.blade.php, register.blade.php
  videos/                    — create.blade.php, index.blade.php, show.blade.php
  layouts/
    app.blade.php            — shared nav + layout
routes/
  web.php
```

**Request flow:**
```
User isi form → VideoController@store → OpenRouterService@generate
→ Parse JSON response → Simpan VideoGeneration ke DB
→ Redirect ke /videos/{id} → User preview + export .txt
```

## Database Schema

### `users` (standard Laravel auth)
- id, name, email, password, timestamps

### `video_generations`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | relasi ke users |
| title | varchar(255) | topik/judul video |
| video_type | varchar(100) | marketing_video, educational_clip, social_media_reel |
| topic | text | deskripsi topik/ide |
| keywords | text | comma-separated keywords |
| target_audience | varchar(255) | target penonton |
| tone | varchar(100) | formal, casual, persuasive, humorous, inspirational |
| duration | varchar(20) | 30s, 60s, 90s, 120s (nullable) |
| script | longtext | full narration script dari AI |
| scenes | json | array scene breakdown dari AI |
| template_used | varchar(100) | nama template jika dipakai (nullable) |
| status | enum | pending, completed, failed |
| created_at | timestamp | |
| updated_at | timestamp | |

**Struktur `scenes` JSON:**
```json
[
  {
    "scene_number": 1,
    "title": "Opening Hook",
    "duration": "5s",
    "narration": "Narration text here...",
    "visual_description": "Camera zooms in on..."
  }
]
```

## Pages & Routes

| Method | Route | Controller@Action | Deskripsi |
|---|---|---|---|
| GET | /register | AuthController@showRegister | Form register |
| POST | /register | AuthController@register | Proses register |
| GET | /login | AuthController@showLogin | Form login |
| POST | /login | AuthController@login | Proses login |
| POST | /logout | AuthController@logout | Logout |
| GET | /dashboard | VideoController@dashboard | Dashboard ringkasan |
| GET | /videos/create | VideoController@create | Form generate |
| POST | /videos | VideoController@store | Trigger AI generate |
| GET | /videos | VideoController@index | History list + search |
| GET | /videos/{id} | VideoController@show | Detail + preview |
| DELETE | /videos/{id} | VideoController@destroy | Hapus generasi |
| GET | /videos/{id}/export | VideoController@export | Download .txt |

Semua route kecuali login/register dilindungi middleware `auth`.

## UI Components

### Form Generate (`/videos/create`)
- Dropdown: Video Type (Marketing Video, Educational Clip, Social Media Reel)
- Input text: Topic/Idea
- Input text: Keywords (comma-separated)
- Input text: Target Audience
- Dropdown: Tone (Formal, Casual, Persuasive, Humorous, Inspirational)
- Dropdown: Duration — optional (30s, 60s, 90s, 120s)
- Section Content Templates: 5 tombol template yang auto-fill form via Alpine.js
- Tombol Generate dengan loading spinner (Alpine.js x-data, disable saat loading)

### Content Templates (pre-filled data)
1. **Product Launch** — marketing_video, formal, 60s
2. **Educational Tutorial** — educational_clip, casual, 90s
3. **Social Media Reel** — social_media_reel, casual, 30s
4. **Company Profile** — marketing_video, formal, 120s
5. **Event Promotion** — marketing_video, persuasive, 60s

### History Page (`/videos`)
- Search bar (filter by title/topic, query ke DB)
- List card per generasi: judul, tipe, tanggal, status badge
- Tombol View + Delete per item
- Empty state jika belum ada generasi

### Detail Page (`/videos/{id}`)
- Header: judul, tipe, tone, durasi, tanggal
- Tab Script | Scene Breakdown (Alpine.js tab switching)
- Script: ditampilkan dalam `<pre>` atau textarea read-only
- Scenes: kartu per scene (nomor, judul, durasi, narasi, visual)
- Tombol Export .txt (link ke `/videos/{id}/export`)
- Tombol Delete (form POST dengan method DELETE)

## AI Integration

### OpenRouterService
```php
// Kirim prompt → terima JSON → parse → return array
public function generate(array $input): array
// Returns: ['script' => '...', 'scenes' => [...]]
// Throws: Exception jika API error
```

**Prompt template:**
```
You are a professional video scriptwriter.

Generate a complete video script with scene breakdown for:
- Video Type: {video_type}
- Topic: {topic}
- Keywords: {keywords}
- Target Audience: {target_audience}
- Tone: {tone}
- Duration: {duration}

Respond ONLY in valid JSON format:
{
  "script": "full narration script here...",
  "scenes": [
    {
      "scene_number": 1,
      "title": "Scene title",
      "duration": "5s",
      "narration": "narration text",
      "visual_description": "visual direction"
    }
  ]
}
```

### Error Handling
- API timeout/error → status `failed`, flash error message ke user
- JSON parse failure → simpan raw response sebagai script, scenes = []
- Loading state via Alpine.js: disable tombol + tampilkan spinner selama POST

### Export .txt Format
```
AI VIDEO GENERATOR
==================
Title: {title}
Type: {video_type}
Tone: {tone}
Duration: {duration}
Generated: {created_at}

FULL SCRIPT
===========
{script}

SCENE BREAKDOWN
===============
Scene 1 — {title} ({duration})
Narration: {narration}
Visual: {visual_description}
...
```

## Environment Variables
```
OPENROUTER_API_KEY=
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
OPENROUTER_MODEL=google/gemini-flash-1.5
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

## Out of Scope
- Multiple variations (2-3 versi dari 1 prompt)
- Scene editing / duration control
- Full video file generation
- Storyboard image generation
