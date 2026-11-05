<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('publisher_ad_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publisher_property_id')->constrained('publisher_properties')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('placement_type', 32);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3)->default('EGP');
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->unsignedSmallInteger('delivery_days')->default(7);
            $table->string('moderation_status', 20)->default('pending');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->index(['publisher_property_id','moderation_status','is_active'], 'publisher_ads_packages_lookup');
        });
        Schema::create('publisher_metric_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publisher_property_id')->constrained('publisher_properties')->cascadeOnDelete();
            $table->date('observed_on');
            $table->string('content_category', 120)->nullable();
            $table->unsignedBigInteger('follower_count')->nullable();
            $table->unsignedBigInteger('views')->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('reactions')->nullable();
            $table->unsignedBigInteger('comments')->nullable();
            $table->unsignedBigInteger('shares')->nullable();
            $table->string('source_type', 24)->default('self_reported');
            $table->string('evidence_url', 2048)->nullable();
            $table->string('verification_status', 20)->default('unverified');
            $table->timestamps();
            $table->index(['publisher_property_id','observed_on']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('publisher_metric_snapshots');
        Schema::dropIfExists('publisher_ad_packages');
    }
};