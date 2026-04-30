<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
};
