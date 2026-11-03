<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('publisher_properties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('property_type', 40);
            $table->string('display_name', 160);
            $table->string('profile_url', 2048);
            $table->string('category', 120);
            $table->string('country_code', 2);
            $table->string('city', 120)->nullable();
            $table->unsignedBigInteger('reported_followers')->nullable();
            $table->text('description')->nullable();
            $table->string('verification_status', 25)->default('pending');
            $table->text('verification_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['country_code', 'category', 'verification_status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publisher_properties');
    }
};
