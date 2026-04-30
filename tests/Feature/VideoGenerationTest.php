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
