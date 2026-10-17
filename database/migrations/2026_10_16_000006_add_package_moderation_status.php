<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('creator_packages',function(Blueprint $table){$table->string('moderation_status',20)->default('pending')->index();});
  DB::table('creator_packages')->where('is_active',true)->update(['moderation_status'=>'approved']);
 }
 public function down():void {
  Schema::table('creator_packages',function(Blueprint $table){$table->dropIndex(['moderation_status']);$table->dropColumn('moderation_status');});
 }
};