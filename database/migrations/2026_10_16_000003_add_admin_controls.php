<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('users',fn(Blueprint $t)=>$t->boolean('is_admin')->default(false));
  Schema::create('moderation_actions',function(Blueprint $t){
   $t->uuid('id')->primary();$t->foreignId('actor_id')->constrained('users');
   $t->string('target_type');$t->uuid('target_id');$t->string('action',50);
   $t->text('reason')->nullable();$t->timestamps();
   $t->index(['target_type','target_id']);
  });
 }
 public function down():void {Schema::dropIfExists('moderation_actions');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('is_admin'));}
};