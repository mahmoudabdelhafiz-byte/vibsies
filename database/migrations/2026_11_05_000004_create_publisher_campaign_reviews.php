<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('publisher_campaign_reviews',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ad_campaign_offer_id')->unique()->constrained('ad_campaign_offers')->restrictOnDelete();
            $table->foreignUuid('publisher_property_id')->constrained('publisher_properties')->restrictOnDelete();
            $table->foreignId('advertiser_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('communication_rating');
            $table->unsignedTinyInteger('timeliness_rating');
            $table->unsignedTinyInteger('quality_rating');
            $table->text('comment')->nullable();
            $table->string('moderation_status',24)->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderation_note')->nullable();
            $table->timestamps();
            $table->index(['publisher_property_id','moderation_status']);
        });
        Schema::create('publisher_campaign_performance_reports',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ad_campaign_offer_id')->unique()->constrained('ad_campaign_offers')->restrictOnDelete();
            $table->foreignUuid('publisher_property_id')->constrained('publisher_properties')->restrictOnDelete();
            $table->string('evidence_source',32)->default('self_reported');
            $table->unsignedBigInteger('views')->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('clicks')->nullable();
            $table->unsignedBigInteger('interactions')->nullable();
            $table->string('evidence_url',2048)->nullable();
            $table->date('measured_on');
            $table->string('verification_status',24)->default('unverified');
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('publisher_campaign_performance_reports');
        Schema::dropIfExists('publisher_campaign_reviews');
    }
};
