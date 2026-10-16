<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('payment_webhook_events',function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->string('provider',64);
   $t->string('external_event_id',191);
   $t->char('payload_sha256',64);
   $t->string('status',24)->default('pending');
   $t->timestampTz('received_at');
   $t->timestampTz('processed_at')->nullable();
   $t->timestamps();
   $t->unique(['provider','external_event_id']);
  });
  Schema::create('payment_idempotency_keys',function(Blueprint $t){
   $t->uuid('id')->primary();
   $t->string('operation',64);
   $t->string('request_key',191);
   $t->char('request_sha256',64);
   $t->string('status',24)->default('reserved');
   $t->timestamps();
   $t->unique(['operation','request_key']);
  });
 }
 public function down():void {
  Schema::dropIfExists('payment_idempotency_keys');
  Schema::dropIfExists('payment_webhook_events');
 }
};
