<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('publisher_campaign_reviews', function (Blueprint $table) {
            $table->text('publisher_response')->nullable();
            $table->timestamp('publisher_responded_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('publisher_campaign_reviews', function (Blueprint $table) {
            $table->dropColumn(['publisher_response', 'publisher_responded_at']);
        });
    }
};
