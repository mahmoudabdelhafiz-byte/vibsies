<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('creator_portfolio_samples',function(Blueprint $t){
   $t->uuid('id')->primary();$t->foreignUuid('creator_profile_id')->constrained()->cascadeOnDelete();
   $t->string('title',160);$t->text('description')->nullable();
   $t->string('video_url',500);$t->string('moderation_status',24)->default('pending');
   $t->timestamps();$t->index(['creator_profile_id','moderation_status']);
  });
 }
 public function down():void {Schema::dropIfExists('creator_portfolio_samples');}
};