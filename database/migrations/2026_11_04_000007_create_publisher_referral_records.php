<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('publisher_referrals', function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publisher_promotion_id')->constrained('publisher_promotions')->restrictOnDelete();
            $table->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('status',24)->default('registered');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();
            $table->index(['publisher_promotion_id','status']);
        });
        Schema::create('publisher_reward_entitlements', function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publisher_property_id')->unique()->constrained('publisher_properties')->restrictOnDelete();
            $table->foreignUuid('publisher_promotion_id')->unique()->constrained('publisher_promotions')->restrictOnDelete();
            $table->unsignedSmallInteger('publisher_share_percent')->default(80);
            $table->unsignedSmallInteger('placement_limit')->default(3);
            $table->unsignedSmallInteger('placements_used')->default(0);
            $table->string('status',24)->default('eligible');
            $table->timestamp('eligible_from');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('publisher_reward_entitlements');
        Schema::dropIfExists('publisher_referrals');
    }
};