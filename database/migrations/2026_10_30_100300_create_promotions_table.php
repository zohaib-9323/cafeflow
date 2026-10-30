<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->string('type', 16); // percentage | fixed
            $table->decimal('value', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable(); // cap for percentage codes
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_site')->default(false); // advertise on the homepage
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
