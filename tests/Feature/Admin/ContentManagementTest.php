<?php

namespace Tests\Feature\Admin;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_faq_appears_on_the_site_and_a_draft_does_not(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $this->post(route('admin.cms.faqs.store'), ['question' => 'Do you have oat milk?', 'answer' => 'Always.', 'is_published' => '1'])->assertRedirect();
        $this->post(route('admin.cms.faqs.store'), ['question' => 'Secret menu?', 'answer' => 'Not yet.'])->assertRedirect();

        $this->assertSame(2, Faq::query()->count());
        $this->get(route('faq'))->assertOk()->assertSee('Do you have oat milk?')->assertDontSee('Secret menu?');
    }

    public function test_faq_answers_are_escaped(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $this->post(route('admin.cms.faqs.store'), ['question' => 'XSS?', 'answer' => '<script>alert(1)</script>', 'is_published' => '1']);

        $this->get(route('faq'))->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_staff_cannot_edit_content(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $this->post(route('admin.cms.faqs.store'), ['question' => 'Q', 'answer' => 'A'])->assertForbidden();
        $this->put(route('admin.cms.homepage.update'), [])->assertForbidden();
    }
}
