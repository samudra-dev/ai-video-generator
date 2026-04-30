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
