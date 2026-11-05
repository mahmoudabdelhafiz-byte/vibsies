<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ad_campaign_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignUuid('publisher_ad_package_id')->constrained('publisher_ad_packages')->restrictOnDelete();
            $table->foreignId('publisher_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('agreed_price_minor');
            $table->char('currency',3);
            $table->string('status',24)->default('offered');
            $table->text('publisher_note')->nullable();
            $table->text('review_note')->nullable();
            $table->string('published_url',2048)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['ad_campaign_id','publisher_ad_package_id'],'ad_offer_campaign_package_unique');
            $table->index(['publisher_user_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('ad_campaign_offers'); }
};