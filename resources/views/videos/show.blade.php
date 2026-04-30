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
