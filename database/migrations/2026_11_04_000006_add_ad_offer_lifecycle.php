<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ad_campaign_offers', function(Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason',1000)->nullable();
            $table->timestamp('resolved_at')->nullable();
        });
        Schema::create('ad_offer_events', function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ad_campaign_offer_id')->constrained('ad_campaign_offers')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type',40);
            $table->string('from_status',24)->nullable();
            $table->string('to_status',24);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['ad_campaign_offer_id','created_at']);
        });
    }
    public function down():void {
        Schema::dropIfExists('ad_offer_events');
        Schema::table('ad_campaign_offers', function(Blueprint $table) {
            $table->dropColumn(['expires_at','cancelled_at','cancellation_reason','resolved_at']);
        });
    }
};