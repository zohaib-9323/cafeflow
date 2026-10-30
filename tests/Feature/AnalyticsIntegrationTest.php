<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->manager()->create());
    }

    public function test_reports_come_from_the_python_service_using_the_server_side_key(): void
    {
        Http::fake(['analytics.test/*' => Http::response(['success' => true, 'message' => 'OK', 'data' => ['total_revenue' => '123.45']])]);

        $response = $this->getJson('/api/v1/analytics/sales?range=7d')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.source', 'python')
            ->assertJsonPath('data.total_revenue', '123.45');

        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Service-Key', 'test-service-key') && str_ends_with($r->url(), '/api/v1/analytics/sales'));
        $this->assertStringNotContainsString('test-service-key', $response->getContent());
    }

    public function test_falls_back_to_laravel_figures_when_the_service_is_down(): void
    {
        Http::fake(['analytics.test/*' => Http::response(['success' => false, 'message' => 'boom'], 500)]);

        $this->getJson('/api/v1/analytics/sales')
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('meta.source', 'laravel-fallback');
    }

    public function test_the_admin_analytics_page_still_renders_without_the_service(): void
    {
        Http::fake(fn () => throw new ConnectionException('refused'));

        $this->actingAs(User::factory()->manager()->create())
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee('unavailable', false);
    }
}
