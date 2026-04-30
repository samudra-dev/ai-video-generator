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
