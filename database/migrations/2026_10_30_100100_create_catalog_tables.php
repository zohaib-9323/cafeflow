<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('sku', 40)->nullable()->unique();
            $table->string('short_description', 255)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('status', 16)->default('active'); // draft | active | archived
            $table->boolean('is_available')->default(true);   // "sold out today" toggle
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('prep_minutes')->default(5);
            $table->unsignedSmallInteger('calories')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category_id', 'sort_order']);
            $table->index(['status', 'is_featured']);
            $table->fullText(['name', 'short_description']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);                      // e.g. "Large · 16oz"
            $table->decimal('price_adjustment', 10, 2)->default(0); // added to product price; may be negative
            $table->decimal('recipe_multiplier', 6, 3)->default(1); // scales ingredient use
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
