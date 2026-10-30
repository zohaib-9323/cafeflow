<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('contact_name', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('address', 255)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('sku', 40)->unique();
            $table->string('unit', 8); // g | kg | ml | l | pcs
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('low_stock_threshold', 12, 3)->default(0);
            $table->decimal('cost_per_unit', 10, 4)->nullable();
            $table->boolean('allow_negative')->default(false);
            $table->timestamp('low_stock_notified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });

        // How much of each ingredient one unit of a product consumes.
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->unique(['product_id', 'inventory_item_id']);
        });

        Schema::create('product_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->decimal('price', 10, 2)->default(0);
            // Optional: the ingredient this add-on consumes (e.g. oat milk, extra shot).
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('inventory_quantity', 12, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addons');
        Schema::dropIfExists('product_recipes');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
    }
};
