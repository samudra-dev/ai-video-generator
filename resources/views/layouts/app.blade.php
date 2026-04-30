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
                <span>✓</span> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-center gap-2">
                <span>✕</span> {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
