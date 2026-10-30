<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('public');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size'); // bytes
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('alt', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 160)->unique();
            $table->longText('body'); // Markdown; raw HTML is stripped when rendered
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('show_in_footer')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cms_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('subtitle', 255)->nullable();
            $table->string('image_path')->nullable();
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_url', 255)->nullable();
            $table->string('placement', 24)->default('home'); // home | menu
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['placement', 'is_active', 'sort_order']);
        });

        // Key/value store for single-instance content: hero, about, contact, hours.
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 255);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 100);
            $table->string('author_title', 120)->nullable();
            $table->string('quote', 600);
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('avatar_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['testimonials', 'faqs', 'site_settings', 'cms_banners', 'cms_pages', 'media'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
