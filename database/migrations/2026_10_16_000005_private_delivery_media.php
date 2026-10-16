<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('deliveries',function(Blueprint $table){
   $table->string('storage_disk',32)->nullable();
   $table->string('storage_path',512)->nullable();
   $table->string('original_filename',255)->nullable();
   $table->string('mime_type',100)->nullable();
   $table->unsignedBigInteger('file_size')->nullable();
   $table->string('sha256',64)->nullable();
   $table->string('media_url',2000)->nullable()->change();
  });
 }
 public function down():void {
  Schema::table('deliveries',function(Blueprint $table){
   $table->dropColumn(['storage_disk','storage_path','original_filename','mime_type','file_size','sha256']);
  });
 }
};