<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('deliveries',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignUuid('booking_id')->constrained()->cascadeOnDelete();$t->foreignId('creator_user_id')->constrained('users');$t->unsignedInteger('version');$t->string('media_url',2000);$t->text('note')->nullable();$t->string('status')->default('submitted');$t->timestamps();$t->unique(['booking_id','version']);});
  Schema::create('disputes',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignUuid('booking_id')->constrained()->cascadeOnDelete();$t->foreignId('raised_by')->constrained('users');$t->text('reason');$t->string('status')->default('open');$t->timestamps();});
 }
 public function down():void {Schema::dropIfExists('disputes');Schema::dropIfExists('deliveries');}
};