@extends('layouts.app')

@section('title', 'Generate Video Script')

@section('content')
<div x-data="{
    loading: false,
    form: {
        video_type: '{{ old('video_type', '') }}',
        topic: `{{ old('topic', '') }}`,
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
