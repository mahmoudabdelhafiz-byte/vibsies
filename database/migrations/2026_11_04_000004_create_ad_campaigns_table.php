<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('title', 160);
            $table->text('brief');
            $table->string('objective', 32);
            $table->string('target_category', 120);
            $table->char('target_country', 2);
            $table->string('target_city', 120)->nullable();
            $table->string('placement_type', 32)->nullable();
            $table->unsignedBigInteger('budget_minor');
            $table->char('currency', 3);
            $table->string('status', 24)->default('draft');
            $table->timestamps();
            $table->index(['status','target_country','target_category']);
        });
    }
    public function down(): void { Schema::dropIfExists('ad_campaigns'); }
};