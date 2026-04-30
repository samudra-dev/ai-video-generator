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
