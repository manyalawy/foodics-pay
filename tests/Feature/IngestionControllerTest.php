<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IngestionControllerTest extends TestCase
{
    public function test_pause_sets_ingestion_flag(): void
    {
        $response = $this->postJson('/api/ingestion/pause');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Ingestion paused.']);
        $this->assertTrue(Cache::get('ingestion_paused'));
    }

    public function test_resume_clears_ingestion_flag(): void
    {
        Cache::put('ingestion_paused', true);

        $response = $this->postJson('/api/ingestion/resume');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Ingestion resumed.']);
        $this->assertNull(Cache::get('ingestion_paused'));
    }

    public function test_status_returns_paused_state(): void
    {
        $this->getJson('/api/ingestion/status')
            ->assertJson(['paused' => false]);

        Cache::put('ingestion_paused', true);

        $this->getJson('/api/ingestion/status')
            ->assertJson(['paused' => true]);
    }
}
