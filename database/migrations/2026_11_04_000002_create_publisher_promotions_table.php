<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('publisher_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('publisher_property_id');
            $table->foreign('publisher_property_id')->references('id')->on('publisher_properties')->cascadeOnDelete();
            $table->string('referral_code', 32)->unique();
            $table->string('post_url', 2048);
            $table->string('post_format', 16);
            $table->boolean('disclosure_confirmed')->default(false);
            $table->string('status', 25)->default('pending');
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['publisher_property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publisher_promotions');
    }
};
