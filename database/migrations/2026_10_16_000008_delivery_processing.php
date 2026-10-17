<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('deliveries',function(Blueprint $table){
   $table->string('processing_status',24)->default('pending');
   $table->string('thumbnail_path',512)->nullable();
   $table->text('processing_error')->nullable();
   $table->timestamp('processed_at')->nullable();
  });
 }
 public function down():void {
  Schema::table('deliveries',fn(Blueprint $table)=>$table->dropColumn(['processing_status','thumbnail_path','processing_error','processed_at']));
 }
};
