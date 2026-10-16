<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('creator_verifications',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignUuid('creator_profile_id')->constrained();$t->string('type',32);$t->string('status',24)->default('pending');$t->string('reference_hash',64)->nullable();$t->foreignId('reviewed_by')->nullable()->constrained('users');$t->timestampTz('reviewed_at')->nullable();$t->timestampTz('expires_at')->nullable();$t->timestamps();});
  Schema::create('payment_intents',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignUuid('booking_id')->unique()->constrained();$t->unsignedBigInteger('amount_minor');$t->char('currency',3);$t->string('status',24)->default('disabled');$t->string('provider_reference')->nullable();$t->timestamps();});
  Schema::create('ledger_accounts',function(Blueprint $t){$t->uuid('id')->primary();$t->string('code')->unique();$t->string('name');$t->char('currency',3);$t->timestamps();});
  Schema::create('ledger_transactions',function(Blueprint $t){$t->uuid('id')->primary();$t->string('external_key')->unique();$t->string('description');$t->timestamps();});
  Schema::create('ledger_entries',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignUuid('ledger_transaction_id')->constrained();$t->foreignUuid('ledger_account_id')->constrained();$t->unsignedBigInteger('debit_minor')->default(0);$t->unsignedBigInteger('credit_minor')->default(0);$t->timestamps();});
 }
 public function down():void {foreach(['ledger_entries','ledger_transactions','ledger_accounts','payment_intents','creator_verifications'] as $name)Schema::dropIfExists($name);}
};