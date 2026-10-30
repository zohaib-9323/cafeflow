<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('pending');
            $table->string('fulfillment_type', 16); // delivery | pickup
            $table->string('payment_method', 16);   // cash | card
            $table->string('payment_status', 16)->default('unpaid'); // unpaid | pending | paid | failed | refunded

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();

            // Contact and delivery details are copied onto the order so it stays an
            // accurate record even if the customer later edits their profile or address.
            $table->string('customer_name', 150);
            $table->string('customer_email');
            $table->string('customer_phone', 32);
            $table->string('delivery_line1')->nullable();
            $table->string('delivery_line2')->nullable();
            $table->string('delivery_city', 100)->nullable();
            $table->string('delivery_postal_code', 20)->nullable();
            $table->string('delivery_instructions', 500)->nullable();
            $table->string('notes', 1000)->nullable();

            $table->timestamp('placed_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamp('inventory_deducted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index(['user_id', 'placed_at']);
            $table->index(['assigned_to', 'status']);
            $table->index('placed_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variation_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots: what the customer actually bought and paid, at the time.
            $table->string('product_name', 150);
            $table->string('variation_name', 80)->nullable();
            $table->decimal('unit_price', 10, 2); // product price + variation + add-ons
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->decimal('recipe_multiplier', 6, 3)->default(1);
            $table->timestamps();

            $table->index(['product_id', 'order_id']);
        });

        Schema::create('order_item_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_addon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->decimal('price', 10, 2);
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at');

            $table->index(['order_id', 'created_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16); // cash | mock | stripe
            $table->string('provider_reference')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3);
            $table->string('status', 16)->default('pending'); // pending | succeeded | failed | refunded | cancelled
            $table->string('failure_reason', 500)->nullable();
            $table->json('meta')->nullable(); // non-sensitive gateway metadata only; never card data
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_reference']);
            $table->index(['order_id', 'status']);
        });

        // Every processed gateway event, so a redelivered webhook is a no-op.
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 16);
            $table->string('event_id', 120);
            $table->string('type', 80);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });

        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();

            $table->index(['promotion_id', 'user_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24); // restock | adjustment | waste | order_consumption | order_reversal
            $table->decimal('quantity_change', 12, 3);
            $table->decimal('quantity_after', 12, 3);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at');

            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['order_id', 'type']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'user_id']);
            $table->index(['product_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        foreach (['reviews', 'inventory_movements', 'promotion_usages', 'payment_webhook_events', 'payments',
                     'order_status_histories', 'order_item_addons', 'order_items', 'orders'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
