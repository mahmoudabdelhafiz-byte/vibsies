<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('publisher_properties',function(Blueprint $table){
            // Nullable for legacy fixtures and records until backfill of canonical properties.
            $table->char('profile_url_fingerprint',64)->nullable()->unique();
        });
    }
    public function down(): void {
        Schema::table('publisher_properties',function(Blueprint $table){
            $table->dropUnique(['profile_url_fingerprint']);
            $table->dropColumn('profile_url_fingerprint');
        });
    }
};
