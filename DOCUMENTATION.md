# AI Video Generator — Project Documentation

**Submitted by:** Samudra Zulqifli
**Date:** April 30, 2026
**Live URL:** https://ai-video-generator-production-313d.up.railway.app
**Task:** Option A — AI Video Generator

---

## 1. Project Overview

A web application that allows authenticated users to generate AI-powered video scripts with detailed scene breakdowns. Users specify the video type, topic, keywords, target audience, tone, and optional duration — the system then uses an LLM to generate a complete narration script along with a scene-by-scene breakdown that includes narration text and visual direction. All generations are persisted in a database, and users can browse their history, search past outputs, view details, delete, and export scripts as plain text files.

---

## 2. Tools & Technologies

| Layer | Technology | Rationale |
|---|---|---|
| **Backend Framework** | Laravel 13 (PHP 8.4) | Mature MVC framework with built-in auth, validation, ORM, migrations |
| **Database** | SQLite (local) / MySQL (production) | SQLite for fast local dev, MySQL on Railway for production |
| **Frontend Templating** | Blade | Server-side rendering — no separate frontend build needed |
| **CSS** | Tailwind CSS (via CDN) | Utility-first, no build pipeline required |
| **JS Interactivity** | Alpine.js (via CDN) | Lightweight reactivity for form state, tabs, loading spinners |
| **AI Provider** | OpenRouter API | Unified API for multiple LLMs; free tier available |
| **AI Model** | `google/gemini-2.0-flash-001` | Fast multimodal model from Google, strong JSON mode support |
| **Deployment** | Railway | Auto-detects PHP/Laravel, simple GitHub integration, MySQL bundled |
| **Testing** | PHPUnit (Laravel Feature Tests) | Built into Laravel, covers HTTP layer end-to-end |

---

## 3. Approach

The project was built using a structured workflow:

1. **Brainstorming** — defined scope, picked stack, selected MVP features (core requirements + content templates as the chosen bonus).
2. **Design specification** — wrote a design doc covering architecture, database schema, routes, AI integration, error handling, and export format.
3. **Implementation plan** — broke the work into 12 sequential tasks with TDD where applicable.
4. **Test-driven development** — for the model and authentication, tests were written first, then implementation followed.
5. **Iterative subagent execution** — each task was implemented and verified independently with tests passing before moving on.
6. **Deployment to Railway** — pushed to GitHub, connected MySQL service, configured environment variables.

### Key Design Decisions

- **No separate frontend build** — Tailwind and Alpine.js are loaded via CDN, eliminating npm/Vite complexity. This keeps deployment simple.
- **Synchronous AI generation** — for an MVP with a 3-day deadline, the controller waits for the AI response inline (10–30 seconds) instead of using queues. The form shows a loading state via Alpine.js.
- **JSON mode response** — the prompt explicitly requests JSON output and uses OpenRouter's `response_format: json_object` to ensure parseable structured data (script + scenes).
- **Graceful degradation** — if the AI API fails or returns malformed JSON, the generation is marked as `failed` and the user is shown a friendly error message instead of a stack trace.
- **User isolation** — every controller action that touches a `VideoGeneration` checks `$video->user_id !== Auth::id()` and returns 403 to prevent users from seeing each other's content.

---

## 4. Architecture & Logic

### High-level Flow

```
User fills form (Blade + Alpine.js)
        ↓
POST /videos → VideoController@store
        ↓
Validate input → Create VideoGeneration record (status: pending)
        ↓
OpenRouterService@generate
        ↓
HTTP POST to OpenRouter API (JSON mode)
        ↓
Parse JSON response (script + scenes)
        ↓
Update record (status: completed, save script + scenes)
        ↓
Redirect to /videos/{id} (preview page with tabs)
        ↓
User can: view, export .txt, delete, or generate another
```

### Folder Structure

```
app/
  Http/Controllers/
    AuthController.php       — register, login, logout
    VideoController.php      — dashboard, create, store, index, show, export, destroy
  Models/
    User.php                 — hasMany VideoGeneration
    VideoGeneration.php      — belongsTo User, scenes cast to array
  Services/
    OpenRouterService.php    — encapsulates AI API call + prompt + response parsing
database/
  migrations/
    *_create_video_generations_table.php
  factories/
    VideoGenerationFactory.php
resources/views/
  layouts/app.blade.php      — shared nav + flash messages
  auth/                      — login, register
  dashboard.blade.php
  videos/
    create.blade.php         — form + templates sidebar (Alpine.js)
    index.blade.php          — history + search + pagination
    show.blade.php           — script/scene tabs (Alpine.js) + export button
routes/web.php
config/services.php          — openrouter config block
tests/Feature/
  AuthTest.php
  VideoGenerationTest.php
  VideoExportTest.php
```

### Database Schema

`video_generations` table:

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | cascade on delete |
| title | string | |
| video_type | string | marketing_video / educational_clip / social_media_reel |
| topic | text | |
| keywords | text | comma-separated |
| target_audience | string | |
| tone | string | formal / casual / persuasive / humorous / inspirational |
| duration | string nullable | 30s / 60s / 90s / 120s |
| script | longtext nullable | full narration from AI |
| scenes | json nullable | array of scene objects |
| template_used | string nullable | name of preset template if used |
| status | enum | pending / completed / failed |
| timestamps | | |

### AI Prompt Logic

The prompt sent to OpenRouter is carefully structured to produce parseable JSON:

```
You are a professional video scriptwriter.

Generate a complete video script with scene breakdown for:
- Video Type: {type}
- Topic: {topic}
- Keywords: {keywords}
- Target Audience: {audience}
- Tone: {tone}
- Duration: {duration}

Respond ONLY in valid JSON format with no markdown, no code blocks:
{
  "script": "full narration script here as a single string",
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

Combined with `response_format: { type: "json_object" }`, this produces consistent, structured output that can be directly stored in the database and rendered in the UI.

---

## 5. Features Implemented

### Required Features (all 5 from spec)

1. **User Authentication** — register, login, logout via Laravel Auth (custom `AuthController`)
2. **Video Generation Form** — all required fields with validation, plus optional duration
3. **AI-Powered Generation** — generates script + scene breakdown via OpenRouter, with loading state and error handling
4. **Generation History** — view all past generations, search by title/topic, delete individual entries, paginated list
5. **Copy & Export** — preview generated content with tabs (Script / Scenes), export as `.txt` file

### Bonus Feature

- **Content Templates** — 5 pre-filled templates (Product Launch, Educational Tutorial, Social Media Reel, Company Profile, Event Promotion) that auto-populate the form via Alpine.js. Each template name is recorded with the generation for reference.

### Quality & UX Touches

- Responsive layout (mobile-friendly with Tailwind)
- Loading spinner on form submit (disables button to prevent double-submits)
- Tab interface on detail page for switching between full script and scene breakdown
- Status badges (Completed / Failed / Pending) with distinct colors
- Empty states with helpful CTAs ("Create your first video script")
- Confirmation prompts before delete actions
- 403 protection — users can never access other users' generations
- Flash messages for success/error feedback
- Search filter that preserves query parameters during pagination

---

## 6. Testing

The application has 16 feature tests covering:

- **Auth (5 tests)** — register, login, invalid credentials, logout, guest redirect
- **Video Generation (7 tests)** — model relations, JSON casting, form rendering, AI mocked generation, history search, delete, user isolation (403)
- **Export (2 tests)** — successful .txt export, 403 on other user's content
- **Sanity (1 test)** — root redirects to login

All tests pass against an in-memory SQLite database using Laravel's `RefreshDatabase` trait. The `OpenRouterService` is mocked in tests to avoid hitting the real AI API.

```
Tests:  16 passed
```

---

## 7. Deployment

The application is deployed on **Railway** with the following setup:

1. GitHub repo connected to Railway
2. MySQL database service provisioned within the same Railway project
3. Environment variables configured (APP_KEY, DB credentials, OpenRouter API key, etc.)
4. Auto-deploys on every push to `main`
5. Migrations run automatically on each deploy via build phase

**Production URL:** https://ai-video-generator-production-313d.up.railway.app

---

## 8. Reflections

### What went well
- The design-first approach prevented scope creep — the spec was decided before any code was written.
- Using CDN-based Tailwind/Alpine.js eliminated frontend build complexity entirely.
- OpenRouter's JSON mode made AI integration reliable; no fragile regex or markdown stripping needed.
- TDD on the model and auth layer caught a couple of small bugs early.

### Trade-offs accepted for the deadline
- Synchronous AI generation (rather than queued/background job) — simpler to demo, but a real user might wait 10–30 seconds at form submit. With more time, a queue with polling/WebSocket updates would be the upgrade.
- No multi-variation generation (only one output per submit) — would require an additional UI flow to compare versions.
- No standalone unit tests for `OpenRouterService` — covered indirectly via the controller mock.

### What I'd add with more time
- Background job queue for async generation
- Real-time progress updates (WebSocket / Livewire)
- Multiple variations per prompt (2–3 outputs)
- Inline scene editing
- Image generation for storyboard preview
- Rate limiting per user

---

## 9. Repository

Source code is available in the project repository on GitHub. The full implementation plan and design specification documents are committed under `docs/superpowers/`.

---

*End of documentation.*
