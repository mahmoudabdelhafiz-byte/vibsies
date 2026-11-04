<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('publisher_properties', function (Blueprint $table) {
            $table->timestamp('verification_expires_at')->nullable();
            $table->timestamp('challenge_submitted_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('publisher_verification_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publisher_property_id')->constrained('publisher_properties')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 35);
            $table->string('decision', 25)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['publisher_property_id','created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('publisher_verification_events');
        Schema::table('publisher_properties', function(Blueprint $table){
            $table->dropForeign(['verified_by_user_id']);
            $table->dropColumn(['verification_expires_at','challenge_submitted_at','verified_by_user_id']);
        });
    }
};
