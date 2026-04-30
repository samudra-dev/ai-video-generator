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
