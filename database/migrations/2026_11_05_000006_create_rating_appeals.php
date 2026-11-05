<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('publisher_review_appeals',function(Blueprint $table){
   $table->uuid('id')->primary();
   $table->foreignUuid('publisher_campaign_review_id')->unique()->constrained('publisher_campaign_reviews')->restrictOnDelete();
   $table->foreignId('publisher_user_id')->constrained('users')->restrictOnDelete();
   $table->text('reason');
   $table->string('status',24)->default('pending');
   $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
   $table->text('resolution_note')->nullable();
   $table->timestamp('resolved_at')->nullable();
   $table->timestamps();
  });
 }
 public function down(): void {Schema::dropIfExists('publisher_review_appeals');}
};
