<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer-specific profile data. Identity and credentials stay on users.
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 32)->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->text('notes')->nullable(); // staff-only notes
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->default('Home');
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city', 100);
            $table->string('postal_code', 20);
            $table->string('instructions', 500)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('customers');
    }
};
