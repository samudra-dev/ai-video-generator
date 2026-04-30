# AI Video Generator Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Laravel 11 web app where authenticated users generate AI-powered video scripts + scene breakdowns, view history, search, delete, and export to .txt.

**Architecture:** Laravel 11 MVC with Blade templates. `OpenRouterService` handles AI API calls and parses JSON (script + scenes). All generations saved to MySQL (Railway prod) / SQLite (local dev). No frontend build step — Tailwind and Alpine.js via CDN.

**Tech Stack:** PHP 8.2, Laravel 11, SQLite (local) / MySQL (Railway), Tailwind CSS CDN, Alpine.js CDN, OpenRouter API (`google/gemini-flash-1.5`)

---

## File Map

```
app/
  Http/Controllers/
    AuthController.php          — register, login, logout
    VideoController.php         — dashboard, create, store, index, show, export, destroy
  Models/
    User.php                    — (exists, add hasMany)
    VideoGeneration.php         — model with scenes cast to array
  Services/
    OpenRouterService.php       — AI API call + JSON parsing
database/
  factories/
    VideoGenerationFactory.php  — test factory
  migrations/
    xxxx_create_video_generations_table.php
resources/views/
  layouts/app.blade.php         — shared nav + flash messages
  auth/
    login.blade.php
    register.blade.php
  dashboard.blade.php
  videos/
    create.blade.php            — form + templates sidebar
    index.blade.php             — history + search + pagination
    show.blade.php              — script/scene tabs + export button
routes/web.php
config/services.php             — add openrouter config
tests/Feature/
  AuthTest.php
  VideoGenerationTest.php
  VideoExportTest.php
```

---

## Task 1: Install PHP, Composer, and Create Laravel Project

**Files:**
- Create: `/Users/samudrazulqifli/developer/ai-video-generator/` (Laravel project root)

- [ ] **Step 1: Install PHP via Homebrew**

```bash
brew install php
php --version
```
Expected: `PHP 8.2.x` or higher

- [ ] **Step 2: Install Composer**

```bash
brew install composer
composer --version
```
Expected: `Composer version 2.x.x`

- [ ] **Step 3: Create Laravel project**

```bash
cd /Users/samudrazulqifli/developer
composer create-project laravel/laravel ai-video-generator
cd ai-video-generator
```

- [ ] **Step 4: Verify Laravel works**

```bash
php artisan --version
```
Expected: `Laravel Framework 11.x.x`

- [ ] **Step 5: Initialize git**

```bash
git init
git add .
git commit -m "chore: init Laravel 11 project"
```

---

## Task 2: Configure Environment

**Files:**
- Modify: `.env`
- Modify: `config/services.php`

- [ ] **Step 1: Configure .env for local SQLite**

Open `.env` and update these lines:

```env
APP_NAME="AI Video Generator"
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# Comment out or remove DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

OPENROUTER_API_KEY=your_key_here
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
OPENROUTER_MODEL=google/gemini-flash-1.5
```

- [ ] **Step 2: Create SQLite database file**

```bash
touch database/database.sqlite
```

- [ ] **Step 3: Add OpenRouter config to config/services.php**

Open `config/services.php` and add inside the return array:

```php
'openrouter' => [
    'key' => env('OPENROUTER_API_KEY'),
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
    'model' => env('OPENROUTER_MODEL', 'google/gemini-flash-1.5'),
],
```

- [ ] **Step 4: Run default migrations**

```bash
php artisan migrate
```
Expected: `Migration table created successfully` + users and other default tables created.

- [ ] **Step 5: Commit**

```bash
git add config/services.php .env.example
git commit -m "chore: configure environment and OpenRouter service config"
```

---

## Task 3: VideoGeneration Migration + Model + Factory

**Files:**
- Create: `database/migrations/xxxx_create_video_generations_table.php`
- Create: `app/Models/VideoGeneration.php`
- Create: `database/factories/VideoGenerationFactory.php`
- Modify: `app/Models/User.php`

- [ ] **Step 1: Write failing test for model**

Create `tests/Feature/VideoGenerationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoGeneration;
use App\Services\OpenRouterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_generation_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $video = VideoGeneration::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $video->user->id);
    }

    public function test_scenes_are_cast_to_array(): void
    {
        $scenes = [['scene_number' => 1, 'title' => 'Opening', 'duration' => '5s', 'narration' => 'Hello', 'visual_description' => 'Camera pans']];
        $video = VideoGeneration::factory()->create(['scenes' => $scenes]);

        $this->assertIsArray($video->fresh()->scenes);
        $this->assertEquals('Opening', $video->fresh()->scenes[0]['title']);
    }

    public function test_authenticated_user_can_view_create_form(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/videos/create');
        $response->assertStatus(200);
        $response->assertViewIs('videos.create');
    }

    public function test_user_can_generate_video_script(): void
    {
        $user = User::factory()->create();

        $this->mock(OpenRouterService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn([
                'script' => 'This is a test script.',
                'scenes' => [
                    ['scene_number' => 1, 'title' => 'Opening', 'duration' => '5s', 'narration' => 'Welcome...', 'visual_description' => 'Camera pans...'],
                ],
            ]);
        });

        $response = $this->actingAs($user)->post('/videos', [
            'video_type' => 'marketing_video',
            'topic' => 'Test Product Launch',
            'keywords' => 'test, product, launch',
            'target_audience' => 'Tech enthusiasts',
            'tone' => 'persuasive',
            'duration' => '60s',
        ]);

        $this->assertDatabaseHas('video_generations', [
            'user_id' => $user->id,
            'status' => 'completed',
            'title' => 'Test Product Launch',
        ]);
        $response->assertRedirect();
    }

    public function test_user_can_search_history(): void
    {
        $user = User::factory()->create();
        VideoGeneration::factory()->create(['user_id' => $user->id, 'title' => 'Apple Product']);
        VideoGeneration::factory()->create(['user_id' => $user->id, 'title' => 'Banana Video']);

        $response = $this->actingAs($user)->get('/videos?search=Apple');

        $response->assertStatus(200);
        $response->assertSee('Apple Product');
        $response->assertDontSee('Banana Video');
    }

    public function test_user_can_delete_generation(): void
    {
        $user = User::factory()->create();
        $video = VideoGeneration::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/videos/{$video->id}");

        $response->assertRedirect(route('videos.index'));
        $this->assertDatabaseMissing('video_generations', ['id' => $video->id]);
    }

    public function test_user_cannot_view_other_users_generation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $video = VideoGeneration::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/videos/{$video->id}");
        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/VideoGenerationTest.php
```
Expected: Multiple failures (model and routes not yet created)

- [ ] **Step 3: Create migration**

```bash
php artisan make:migration create_video_generations_table
```

Open the new migration file in `database/migrations/` and replace the `up()` method:

```php
public function up(): void
{
    Schema::create('video_generations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->string('video_type');
        $table->text('topic');
        $table->text('keywords');
        $table->string('target_audience');
        $table->string('tone');
        $table->string('duration')->nullable();
        $table->longText('script')->nullable();
        $table->json('scenes')->nullable();
        $table->string('template_used')->nullable();
        $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('video_generations');
}
```

- [ ] **Step 4: Create VideoGeneration model**

Create `app/Models/VideoGeneration.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'video_type', 'topic', 'keywords',
        'target_audience', 'tone', 'duration', 'script', 'scenes',
        'template_used', 'status',
    ];

    protected $casts = [
        'scenes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Add hasMany to User model**

Open `app/Models/User.php` and add inside the class:

```php
public function videoGenerations()
{
    return $this->hasMany(VideoGeneration::class);
}
```

- [ ] **Step 6: Create VideoGeneration factory**

Create `database/factories/VideoGenerationFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoGenerationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'video_type' => $this->faker->randomElement(['marketing_video', 'educational_clip', 'social_media_reel']),
            'topic' => $this->faker->paragraph(),
            'keywords' => implode(', ', $this->faker->words(5)),
            'target_audience' => $this->faker->sentence(),
            'tone' => $this->faker->randomElement(['formal', 'casual', 'persuasive', 'humorous', 'inspirational']),
            'duration' => $this->faker->randomElement(['30s', '60s', '90s', '120s', null]),
            'script' => $this->faker->paragraphs(3, true),
            'scenes' => [
                [
                    'scene_number' => 1,
                    'title' => 'Opening',
                    'duration' => '10s',
                    'narration' => $this->faker->sentence(),
                    'visual_description' => $this->faker->sentence(),
                ],
            ],
            'template_used' => null,
            'status' => 'completed',
        ];
    }
}
```

- [ ] **Step 7: Run migration**

```bash
php artisan migrate
```
Expected: `video_generations` table created.

- [ ] **Step 8: Commit**

```bash
git add .
git commit -m "feat: add VideoGeneration model, migration, and factory"
```

---

## Task 4: Authentication

**Files:**
- Create: `app/Http/Controllers/AuthController.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/register.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing auth tests**

Create `tests/Feature/AuthTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run auth tests to verify they fail**

```bash
php artisan test tests/Feature/AuthTest.php
```
Expected: Failures (routes and controller not yet created)

- [ ] **Step 3: Create AuthController**

Create `app/Http/Controllers/AuthController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
```

- [ ] **Step 4: Set up routes/web.php**

Replace the entire content of `routes/web.php`:

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [VideoController::class, 'dashboard'])->name('dashboard');
    Route::get('/videos/create', [VideoController::class, 'create'])->name('videos.create');
    Route::post('/videos', [VideoController::class, 'store'])->name('videos.store');
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::get('/videos/{video}/export', [VideoController::class, 'export'])->name('videos.export');
    Route::delete('/videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');
});
```

- [ ] **Step 5: Run auth tests — they should still fail (views missing)**

```bash
php artisan test tests/Feature/AuthTest.php
```
Expected: Failures about missing views

- [ ] **Step 6: Commit routes and controller**

```bash
git add .
git commit -m "feat: add AuthController and routes"
```

---

## Task 5: App Layout + Auth Views

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/register.blade.php`

- [ ] **Step 1: Create layouts/app.blade.php**

Create `resources/views/layouts/app.blade.php`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AI Video Generator')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-indigo-700 text-white px-6 py-4 shadow-md">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-tight">🎬 AI Video Generator</a>
            @auth
            <div class="flex items-center gap-4 text-sm">
                <span class="text-indigo-200">Hi, {{ Auth::user()->name }}</span>
                <a href="{{ route('videos.create') }}" class="bg-white text-indigo-700 px-4 py-2 rounded-lg font-semibold hover:bg-indigo-50 transition">+ Generate</a>
                <a href="{{ route('videos.index') }}" class="hover:underline text-indigo-100">History</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="hover:underline text-indigo-100">Logout</button>
                </form>
            </div>
            @endauth
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-center gap-2">
                <span>❌</span> {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
```

- [ ] **Step 2: Create auth/login.blade.php**

Create `resources/views/auth/login.blade.php`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — AI Video Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="text-4xl mb-2">🎬</div>
            <h1 class="text-2xl font-bold text-gray-900">AI Video Generator</h1>
            <p class="text-gray-500 text-sm mt-1">Sign in to your account</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/login" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    placeholder="you@example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    placeholder="••••••••">
            </div>
            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition">
                Sign In
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Don't have an account? <a href="{{ route('register') }}" class="text-indigo-600 font-medium hover:underline">Register</a>
        </p>
    </div>
</body>
</html>
```

- [ ] **Step 3: Create auth/register.blade.php**

Create `resources/views/auth/register.blade.php`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — AI Video Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="text-4xl mb-2">🎬</div>
            <h1 class="text-2xl font-bold text-gray-900">Create Account</h1>
            <p class="text-gray-500 text-sm mt-1">Start generating AI video scripts</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="/register" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    placeholder="Your name">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    placeholder="you@example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    placeholder="Min. 8 characters">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    placeholder="Repeat password">
            </div>
            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition">
                Create Account
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 font-medium hover:underline">Sign in</a>
        </p>
    </div>
</body>
</html>
```

- [ ] **Step 4: Run auth tests — should pass now**

```bash
php artisan test tests/Feature/AuthTest.php
```
Expected: All 5 tests PASS

- [ ] **Step 5: Commit**

```bash
git add .
git commit -m "feat: add auth views and shared layout"
```

---

## Task 6: OpenRouterService

**Files:**
- Create: `app/Services/OpenRouterService.php`

- [ ] **Step 1: Create OpenRouterService**

Create `app/Services/OpenRouterService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenRouterService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->model = config('services.openrouter.model', 'google/gemini-flash-1.5');
    }

    public function generate(array $input): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'AI Video Generator',
        ])->timeout(90)->post($this->baseUrl . '/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $this->buildPrompt($input)],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            throw new \Exception('AI API request failed: ' . $response->status() . ' ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        $data = json_decode($content, true);

        if (!$data || !isset($data['script'])) {
            return ['script' => $content ?? 'No content returned.', 'scenes' => []];
        }

        return [
            'script' => $data['script'] ?? '',
            'scenes' => $data['scenes'] ?? [],
        ];
    }

    private function buildPrompt(array $input): string
    {
        $duration = !empty($input['duration']) ? "- Duration: {$input['duration']}" : '';
        $type = str_replace('_', ' ', $input['video_type']);

        return <<<PROMPT
You are a professional video scriptwriter.

Generate a complete video script with scene breakdown for:
- Video Type: {$type}
- Topic: {$input['topic']}
- Keywords: {$input['keywords']}
- Target Audience: {$input['target_audience']}
- Tone: {$input['tone']}
{$duration}

Respond ONLY in valid JSON format with no markdown, no code blocks:
{
  "script": "full narration script here as a single string",
  "scenes": [
    {
      "scene_number": 1,
      "title": "Scene title",
      "duration": "5s",
      "narration": "narration text for this scene",
      "visual_description": "visual direction for this scene"
    }
  ]
}
PROMPT;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add .
git commit -m "feat: add OpenRouterService for AI generation"
```

---

## Task 7: VideoController

**Files:**
- Create: `app/Http/Controllers/VideoController.php`

- [ ] **Step 1: Create VideoController**

Create `app/Http/Controllers/VideoController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\VideoGeneration;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    private array $templates = [
        'product_launch' => [
            'name' => 'Product Launch',
            'video_type' => 'marketing_video',
            'topic' => 'Introducing our revolutionary new product that solves your biggest problem',
            'keywords' => 'launch, innovation, solution, exclusive, limited',
            'target_audience' => 'Early adopters and tech enthusiasts aged 25-40',
            'tone' => 'persuasive',
            'duration' => '60s',
        ],
        'educational_tutorial' => [
            'name' => 'Educational Tutorial',
            'video_type' => 'educational_clip',
            'topic' => 'Step-by-step guide to mastering a new skill quickly and effectively',
            'keywords' => 'learn, tutorial, how-to, beginner, tips, guide',
            'target_audience' => 'Beginners and students looking to learn new skills',
            'tone' => 'casual',
            'duration' => '90s',
        ],
        'social_media_reel' => [
            'name' => 'Social Media Reel',
            'video_type' => 'social_media_reel',
            'topic' => 'Quick engaging content that hooks the audience in the first 3 seconds',
            'keywords' => 'trending, viral, engaging, fun, share, hook',
            'target_audience' => 'Social media users aged 18-30',
            'tone' => 'casual',
            'duration' => '30s',
        ],
        'company_profile' => [
            'name' => 'Company Profile',
            'video_type' => 'marketing_video',
            'topic' => 'Who we are, what we do, and why we are the best choice for you',
            'keywords' => 'company, mission, values, team, excellence, trust',
            'target_audience' => 'Potential clients, investors, and partners',
            'tone' => 'formal',
            'duration' => '120s',
        ],
        'event_promotion' => [
            'name' => 'Event Promotion',
            'video_type' => 'marketing_video',
            'topic' => 'Join us for an unforgettable industry event experience you cannot miss',
            'keywords' => 'event, register, limited seats, exclusive, community, network',
            'target_audience' => 'Industry professionals and enthusiasts',
            'tone' => 'persuasive',
            'duration' => '60s',
        ],
    ];

    public function dashboard()
    {
        $userId = Auth::id();
        $stats = [
            'total' => VideoGeneration::where('user_id', $userId)->count(),
            'completed' => VideoGeneration::where('user_id', $userId)->where('status', 'completed')->count(),
            'failed' => VideoGeneration::where('user_id', $userId)->where('status', 'failed')->count(),
            'recent' => VideoGeneration::where('user_id', $userId)->latest()->take(4)->get(),
        ];

        return view('dashboard', compact('stats'));
    }

    public function create()
    {
        return view('videos.create', ['templates' => $this->templates]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'video_type' => 'required|in:marketing_video,educational_clip,social_media_reel',
            'topic' => 'required|string|max:500',
            'keywords' => 'required|string|max:255',
            'target_audience' => 'required|string|max:255',
            'tone' => 'required|in:formal,casual,persuasive,humorous,inspirational',
            'duration' => 'nullable|in:30s,60s,90s,120s',
            'template_used' => 'nullable|string|max:100',
        ]);

        $generation = VideoGeneration::create([
            'user_id' => Auth::id(),
            'title' => $validated['topic'],
            'video_type' => $validated['video_type'],
            'topic' => $validated['topic'],
            'keywords' => $validated['keywords'],
            'target_audience' => $validated['target_audience'],
            'tone' => $validated['tone'],
            'duration' => $validated['duration'] ?? null,
            'template_used' => $validated['template_used'] ?? null,
            'status' => 'pending',
        ]);

        try {
            $service = app(OpenRouterService::class);
            $result = $service->generate($validated);

            $generation->update([
                'script' => $result['script'],
                'scenes' => $result['scenes'],
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            $generation->update(['status' => 'failed']);
            return redirect()->route('videos.show', $generation)
                ->with('error', 'AI generation failed. Please try again.');
        }

        return redirect()->route('videos.show', $generation)
            ->with('success', 'Video script generated successfully!');
    }

    public function index(Request $request)
    {
        $query = VideoGeneration::where('user_id', Auth::id())->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        $generations = $query->paginate(10)->withQueryString();

        return view('videos.index', compact('generations'));
    }

    public function show(VideoGeneration $video)
    {
        abort_if($video->user_id !== Auth::id(), 403);
        return view('videos.show', compact('video'));
    }

    public function export(VideoGeneration $video)
    {
        abort_if($video->user_id !== Auth::id(), 403);

        $content = $this->buildExportContent($video);
        $filename = 'video-script-' . $video->id . '.txt';

        return response($content)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function destroy(VideoGeneration $video)
    {
        abort_if($video->user_id !== Auth::id(), 403);
        $video->delete();
        return redirect()->route('videos.index')->with('success', 'Generation deleted successfully.');
    }

    private function buildExportContent(VideoGeneration $video): string
    {
        $sep = str_repeat('=', 50);
        $lines = [
            'AI VIDEO GENERATOR',
            $sep,
            'Title: ' . $video->title,
            'Type: ' . str_replace('_', ' ', ucwords($video->video_type, '_')),
            'Tone: ' . ucfirst($video->tone),
            'Duration: ' . ($video->duration ?? 'Not specified'),
            'Generated: ' . $video->created_at->format('Y-m-d H:i:s'),
            '',
            'FULL SCRIPT',
            $sep,
            $video->script ?? 'No script available.',
            '',
            'SCENE BREAKDOWN',
            $sep,
        ];

        foreach ($video->scenes ?? [] as $scene) {
            $lines[] = '';
            $lines[] = "Scene {$scene['scene_number']} — {$scene['title']} ({$scene['duration']})";
            $lines[] = 'Narration: ' . $scene['narration'];
            $lines[] = 'Visual:    ' . $scene['visual_description'];
        }

        return implode("\n", $lines);
    }
}
```

- [ ] **Step 2: Run VideoGenerationTest — most tests should pass now**

```bash
php artisan test tests/Feature/VideoGenerationTest.php
```
Expected: Some pass, some fail (views not yet created)

- [ ] **Step 3: Commit**

```bash
git add .
git commit -m "feat: add VideoController with all actions"
```

---

## Task 8: Dashboard View

**Files:**
- Create: `resources/views/dashboard.blade.php`

- [ ] **Step 1: Create dashboard.blade.php**

Create `resources/views/dashboard.blade.php`:

```html
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-500 text-sm mt-1">Welcome back, {{ Auth::user()->name }}</p>
        </div>
        <a href="{{ route('videos.create') }}"
            class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-indigo-700 transition flex items-center gap-2">
            <span>+</span> Generate Video Script
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Total Generations</p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Completed</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['completed'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Failed</p>
            <p class="text-3xl font-bold text-red-500 mt-1">{{ $stats['failed'] }}</p>
        </div>
    </div>

    {{-- Recent Generations --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Recent Generations</h2>
            <a href="{{ route('videos.index') }}" class="text-indigo-600 text-sm hover:underline">View all</a>
        </div>

        @if($stats['recent']->isEmpty())
            <div class="px-6 py-12 text-center text-gray-400">
                <div class="text-4xl mb-2">🎬</div>
                <p class="font-medium">No generations yet</p>
                <p class="text-sm mt-1">
                    <a href="{{ route('videos.create') }}" class="text-indigo-600 hover:underline">Create your first video script</a>
                </p>
            </div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($stats['recent'] as $video)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <div>
                        <p class="font-medium text-gray-900 truncate max-w-xs">{{ $video->title }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-400 capitalize">{{ str_replace('_', ' ', $video->video_type) }}</span>
                            <span class="text-gray-300">·</span>
                            <span class="text-xs text-gray-400">{{ $video->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($video->status === 'completed')
                            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Completed</span>
                        @elseif($video->status === 'failed')
                            <span class="px-2.5 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">Failed</span>
                        @else
                            <span class="px-2.5 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">Pending</span>
                        @endif
                        <a href="{{ route('videos.show', $video) }}" class="text-indigo-600 text-sm hover:underline">View</a>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add .
git commit -m "feat: add dashboard view"
```

---

## Task 9: Video Create View

**Files:**
- Create: `resources/views/videos/create.blade.php`

- [ ] **Step 1: Create videos/create.blade.php**

Create `resources/views/videos/create.blade.php`:

```html
@extends('layouts.app')

@section('title', 'Generate Video Script')

@section('content')
<div x-data="{
    loading: false,
    form: {
        video_type: '{{ old('video_type', '') }}',
        topic: '{{ old('topic', '') }}',
        keywords: '{{ old('keywords', '') }}',
        target_audience: '{{ old('target_audience', '') }}',
        tone: '{{ old('tone', '') }}',
        duration: '{{ old('duration', '') }}',
        template_used: ''
    },
    applyTemplate(tpl) {
        this.form.video_type = tpl.video_type;
        this.form.topic = tpl.topic;
        this.form.keywords = tpl.keywords;
        this.form.target_audience = tpl.target_audience;
        this.form.tone = tpl.tone;
        this.form.duration = tpl.duration;
        this.form.template_used = tpl.name;
    }
}">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Generate Video Script</h1>
        <p class="text-gray-500 text-sm mt-1">Fill in the details or pick a template to get started</p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- Templates Sidebar --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <h2 class="font-semibold text-gray-700 text-sm mb-3">Quick Templates</h2>
                <div class="space-y-2">
                    @foreach($templates as $key => $tpl)
                    <button type="button"
                        @click="applyTemplate({{ json_encode($tpl) }})"
                        class="w-full text-left px-3 py-2.5 rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 transition text-sm">
                        <p class="font-medium text-gray-800">{{ $tpl['name'] }}</p>
                        <p class="text-gray-400 text-xs mt-0.5 capitalize">{{ str_replace('_', ' ', $tpl['video_type']) }} · {{ $tpl['tone'] }}</p>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Main Form --}}
        <div class="lg:col-span-3">
            <form method="POST" action="{{ route('videos.store') }}"
                @submit="loading = true"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                @csrf

                <input type="hidden" name="template_used" x-model="form.template_used">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Video Type <span class="text-red-500">*</span></label>
                        <select name="video_type" x-model="form.video_type" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition bg-white">
                            <option value="">Select type...</option>
                            <option value="marketing_video">Marketing Video</option>
                            <option value="educational_clip">Educational Clip</option>
                            <option value="social_media_reel">Social Media Reel</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tone <span class="text-red-500">*</span></label>
                        <select name="tone" x-model="form.tone" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition bg-white">
                            <option value="">Select tone...</option>
                            <option value="formal">Formal</option>
                            <option value="casual">Casual</option>
                            <option value="persuasive">Persuasive</option>
                            <option value="humorous">Humorous</option>
                            <option value="inspirational">Inspirational</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Topic / Idea <span class="text-red-500">*</span></label>
                    <textarea name="topic" x-model="form.topic" required rows="3"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition resize-none"
                        placeholder="Describe the topic or idea for your video..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keywords <span class="text-red-500">*</span></label>
                    <input type="text" name="keywords" x-model="form.keywords" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                        placeholder="keyword1, keyword2, keyword3">
                    <p class="text-gray-400 text-xs mt-1">Separate with commas</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Audience <span class="text-red-500">*</span></label>
                    <input type="text" name="target_audience" x-model="form.target_audience" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                        placeholder="e.g. Young professionals aged 25-35">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (optional)</label>
                    <select name="duration" x-model="form.duration"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition bg-white">
                        <option value="">No preference</option>
                        <option value="30s">30 seconds</option>
                        <option value="60s">60 seconds</option>
                        <option value="90s">90 seconds</option>
                        <option value="120s">120 seconds</option>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loading"
                        class="w-full bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 transition disabled:opacity-60 flex items-center justify-center gap-2">
                        <template x-if="loading">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="loading ? 'Generating Script...' : '🎬 Generate Script'"></span>
                    </button>
                    <p class="text-center text-gray-400 text-xs mt-2">This may take 10-30 seconds</p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add .
git commit -m "feat: add video create form with templates and loading state"
```

---

## Task 10: Video Index + Show Views

**Files:**
- Create: `resources/views/videos/index.blade.php`
- Create: `resources/views/videos/show.blade.php`

- [ ] **Step 1: Create videos/index.blade.php**

Create `resources/views/videos/index.blade.php`:

```html
@extends('layouts.app')

@section('title', 'Generation History')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Generation History</h1>
        <a href="{{ route('videos.create') }}"
            class="bg-indigo-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-indigo-700 transition text-sm">
            + New Generation
        </a>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('videos.index') }}">
        <div class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                placeholder="Search by title or topic...">
            <button type="submit"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl font-medium transition">
                Search
            </button>
            @if(request('search'))
                <a href="{{ route('videos.index') }}"
                    class="bg-white border border-gray-300 text-gray-500 px-4 py-2.5 rounded-xl hover:bg-gray-50 transition">
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- Results --}}
    @if($generations->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 py-16 text-center text-gray-400">
            <div class="text-4xl mb-3">🎬</div>
            @if(request('search'))
                <p class="font-medium">No results for "{{ request('search') }}"</p>
                <p class="text-sm mt-1"><a href="{{ route('videos.index') }}" class="text-indigo-600 hover:underline">Clear search</a></p>
            @else
                <p class="font-medium">No generations yet</p>
                <p class="text-sm mt-1"><a href="{{ route('videos.create') }}" class="text-indigo-600 hover:underline">Create your first video script</a></p>
            @endif
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="divide-y divide-gray-50">
                @foreach($generations as $video)
                <div class="px-6 py-4 flex items-start justify-between gap-4 hover:bg-gray-50 transition">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ $video->title }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-400">
                            <span class="capitalize">{{ str_replace('_', ' ', $video->video_type) }}</span>
                            <span>·</span>
                            <span class="capitalize">{{ $video->tone }}</span>
                            @if($video->duration)
                                <span>·</span>
                                <span>{{ $video->duration }}</span>
                            @endif
                            <span>·</span>
                            <span>{{ $video->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        @if($video->status === 'completed')
                            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Completed</span>
                        @elseif($video->status === 'failed')
                            <span class="px-2.5 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">Failed</span>
                        @else
                            <span class="px-2.5 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">Pending</span>
                        @endif

                        <a href="{{ route('videos.show', $video) }}"
                            class="text-indigo-600 text-sm font-medium hover:underline">View</a>

                        <form method="POST" action="{{ route('videos.destroy', $video) }}"
                            onsubmit="return confirm('Delete this generation?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 text-sm hover:text-red-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        <div>{{ $generations->links() }}</div>
    @endif
</div>
@endsection
```

- [ ] **Step 2: Create videos/show.blade.php**

Create `resources/views/videos/show.blade.php`:

```html
@extends('layouts.app')

@section('title', $video->title)

@section('content')
<div x-data="{ tab: 'script' }">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('videos.index') }}" class="text-indigo-600 text-sm hover:underline flex items-center gap-1 mb-2">
                ← Back to History
            </a>
            <h1 class="text-2xl font-bold text-gray-900 leading-tight">{{ $video->title }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-2 text-sm text-gray-500">
                <span class="capitalize">{{ str_replace('_', ' ', $video->video_type) }}</span>
                <span>·</span>
                <span class="capitalize">{{ $video->tone }}</span>
                @if($video->duration)
                    <span>·</span>
                    <span>{{ $video->duration }}</span>
                @endif
                <span>·</span>
                <span>{{ $video->created_at->format('M d, Y H:i') }}</span>
                @if($video->template_used)
                    <span>·</span>
                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded-full">{{ $video->template_used }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3 flex-shrink-0">
            @if($video->status === 'completed')
                <a href="{{ route('videos.export', $video) }}"
                    class="bg-green-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-green-700 transition text-sm flex items-center gap-2">
                    ⬇ Export .txt
                </a>
            @endif
            <form method="POST" action="{{ route('videos.destroy', $video) }}"
                onsubmit="return confirm('Delete this generation?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="border border-red-300 text-red-500 px-4 py-2 rounded-xl text-sm hover:bg-red-50 transition">
                    Delete
                </button>
            </form>
        </div>
    </div>

    @if($video->status === 'failed')
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
            Generation failed. Please <a href="{{ route('videos.create') }}" class="underline font-medium">try again</a>.
        </div>
    @endif

    @if($video->status === 'completed')
        {{-- Tabs --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="border-b border-gray-100 flex">
                <button @click="tab = 'script'"
                    :class="tab === 'script' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-4 text-sm transition">
                    📄 Full Script
                </button>
                <button @click="tab = 'scenes'"
                    :class="tab === 'scenes' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-4 text-sm transition">
                    🎬 Scene Breakdown
                    @if($video->scenes)
                        <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ count($video->scenes) }}</span>
                    @endif
                </button>
            </div>

            {{-- Script Tab --}}
            <div x-show="tab === 'script'" class="p-6">
                @if($video->script)
                    <div class="bg-gray-50 rounded-xl p-5 text-gray-700 text-sm leading-relaxed whitespace-pre-wrap font-mono">{{ $video->script }}</div>
                @else
                    <p class="text-gray-400 text-center py-8">No script available.</p>
                @endif
            </div>

            {{-- Scenes Tab --}}
            <div x-show="tab === 'scenes'" class="p-6">
                @if($video->scenes && count($video->scenes) > 0)
                    <div class="space-y-4">
                        @foreach($video->scenes as $scene)
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="bg-indigo-50 px-4 py-3 flex items-center justify-between">
                                <span class="font-semibold text-indigo-800 text-sm">Scene {{ $scene['scene_number'] }} — {{ $scene['title'] }}</span>
                                <span class="text-xs bg-indigo-100 text-indigo-600 px-2.5 py-1 rounded-full font-medium">{{ $scene['duration'] }}</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Narration</p>
                                    <p class="text-sm text-gray-700 leading-relaxed">{{ $scene['narration'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Visual Direction</p>
                                    <p class="text-sm text-gray-700 leading-relaxed">{{ $scene['visual_description'] }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400 text-center py-8">No scene breakdown available.</p>
                @endif
            </div>
        </div>

        {{-- Input Summary --}}
        <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-700 text-sm mb-4">Generation Input</h2>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">Keywords</dt>
                    <dd class="text-gray-700 mt-1">{{ $video->keywords }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">Target Audience</dt>
                    <dd class="text-gray-700 mt-1">{{ $video->target_audience }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">Topic</dt>
                    <dd class="text-gray-700 mt-1">{{ $video->topic }}</dd>
                </div>
            </dl>
        </div>
    @endif
</div>
@endsection
```

- [ ] **Step 3: Run all tests**

```bash
php artisan test
```
Expected: All tests PASS

- [ ] **Step 4: Commit**

```bash
git add .
git commit -m "feat: add video index, show views, and export functionality"
```

---

## Task 11: Export Test + Final Test Run

**Files:**
- Create: `tests/Feature/VideoExportTest.php`

- [ ] **Step 1: Create VideoExportTest.php**

Create `tests/Feature/VideoExportTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_script_as_txt(): void
    {
        $user = User::factory()->create();
        $video = VideoGeneration::factory()->create([
            'user_id' => $user->id,
            'script' => 'This is the full script content.',
            'scenes' => [
                [
                    'scene_number' => 1,
                    'title' => 'Opening',
                    'duration' => '5s',
                    'narration' => 'Welcome to our product.',
                    'visual_description' => 'Camera zooms in on product.',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get("/videos/{$video->id}/export");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('This is the full script content.', $response->getContent());
        $this->assertStringContainsString('Scene 1', $response->getContent());
        $this->assertStringContainsString('Welcome to our product.', $response->getContent());
    }

    public function test_user_cannot_export_other_users_script(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $video = VideoGeneration::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/videos/{$video->id}/export");

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```
Expected: All tests PASS with output like:
```
Tests:  14 passed
```

- [ ] **Step 3: Start dev server and manually verify**

```bash
php artisan serve
```
Open `http://localhost:8000` in browser, register an account, fill the form, generate a script, view history, export .txt.

- [ ] **Step 4: Commit**

```bash
git add .
git commit -m "feat: add export test and complete full test suite"
```

---

## Task 12: Deploy to Railway

- [ ] **Step 1: Create GitHub repository and push**

```bash
# Create repo on github.com first, then:
git remote add origin https://github.com/YOUR_USERNAME/ai-video-generator.git
git branch -M main
git push -u origin main
```

- [ ] **Step 2: Sign up and set up Railway**

1. Go to [railway.app](https://railway.app) and sign up with GitHub
2. Click **New Project** → **Deploy from GitHub repo** → select `ai-video-generator`
3. Railway will auto-detect PHP/Laravel

- [ ] **Step 3: Add MySQL database on Railway**

1. In Railway project, click **+ New** → **Database** → **MySQL**
2. Click on the MySQL service → **Variables** tab → copy the `MYSQL_URL` or individual connection values

- [ ] **Step 4: Set environment variables on Railway**

In Railway → your Laravel service → **Variables** tab, add:

```
APP_NAME=AI Video Generator
APP_ENV=production
APP_KEY=         (run: php artisan key:generate --show to get value)
APP_DEBUG=false
APP_URL=https://your-app.railway.app

DB_CONNECTION=mysql
DB_HOST=         (from Railway MySQL service)
DB_PORT=3306
DB_DATABASE=     (from Railway MySQL service)
DB_USERNAME=     (from Railway MySQL service)
DB_PASSWORD=     (from Railway MySQL service)

OPENROUTER_API_KEY=your_key_here
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
OPENROUTER_MODEL=google/gemini-flash-1.5
```

- [ ] **Step 5: Add nixpacks.toml for Railway PHP config**

Create `nixpacks.toml` in project root:

```toml
[phases.setup]
nixPkgs = ["php82", "php82Extensions.pdo", "php82Extensions.pdo_mysql", "php82Extensions.mbstring", "php82Extensions.tokenizer", "php82Extensions.xml", "php82Extensions.ctype", "php82Extensions.fileinfo", "php82Extensions.json", "composer"]

[phases.install]
cmds = ["composer install --no-dev --optimize-autoloader"]

[phases.build]
cmds = ["php artisan config:cache", "php artisan route:cache", "php artisan view:cache", "php artisan migrate --force"]

[start]
cmd = "php artisan serve --host=0.0.0.0 --port=$PORT"
```

- [ ] **Step 6: Push nixpacks config and trigger deploy**

```bash
git add nixpacks.toml
git commit -m "chore: add Railway nixpacks config"
git push
```

Railway will auto-deploy. Watch the build logs in the Railway dashboard.

- [ ] **Step 7: Verify live URL**

Once deployed, open the Railway URL and:
- Register a new account
- Generate a video script
- Check history page
- Export .txt file
- Confirm everything works on the live URL

- [ ] **Step 8: Get your OpenRouter API key**

1. Go to [openrouter.ai](https://openrouter.ai)
2. Sign up → Dashboard → API Keys → Create key
3. The `google/gemini-flash-1.5` model has a free tier
4. Add the key to Railway environment variables

---

## Self-Review

**Spec coverage check:**
- ✅ User Authentication (register, login, logout) — Task 4
- ✅ Video Generation Form (type, topic, keywords, audience, tone, duration) — Task 9
- ✅ AI-Powered Generation with loading state + error handling — Tasks 6, 7
- ✅ Generation History (view, search, delete) — Tasks 7, 10
- ✅ Export script as .txt — Tasks 7, 10, 11
- ✅ Content Templates — Task 9 (sidebar in create form)
- ✅ Deploy to Railway — Task 12
- ✅ Auth middleware on all protected routes — Task 4
- ✅ User isolation (users can't see each other's generations) — Task 7

**Placeholder scan:** None found.

**Type consistency:** `VideoGeneration` model, factory, and controller all use consistent field names matching the migration. `scenes` is cast to `array` in model and treated as array throughout views.
