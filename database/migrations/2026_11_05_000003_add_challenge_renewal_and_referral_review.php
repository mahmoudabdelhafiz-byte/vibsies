<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('publisher_properties',function(Blueprint $table) {
            $table->unsignedSmallInteger('challenge_renewal_count')->default(0);
            $table->timestamp('challenge_last_renewed_at')->nullable();
        });
        Schema::table('publisher_referrals',function(Blueprint $table) {
            $table->string('review_status',24)->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });
        Schema::create('publisher_referral_review_events',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publisher_referral_id')->constrained('publisher_referrals')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision',24);
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('publisher_referral_review_events');
        Schema::table('publisher_referrals',function(Blueprint $table) {
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropColumn(['review_status','reviewed_by_user_id','reviewed_at','review_note']);
        });
        Schema::table('publisher_properties',function(Blueprint $table) {
            $table->dropColumn(['challenge_renewal_count','challenge_last_renewed_at']);
        });
    }
};
