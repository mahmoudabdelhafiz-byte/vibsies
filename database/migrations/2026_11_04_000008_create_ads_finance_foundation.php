<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ad_payment_records',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ad_campaign_offer_id')->unique()->constrained('ad_campaign_offers')->restrictOnDelete();
            $table->char('currency',3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('provider',40)->nullable();
            $table->string('provider_reference',160)->nullable()->unique();
            $table->string('status',24)->default('unfunded');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ad_ledger_batches',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ad_campaign_offer_id')->unique()->constrained('ad_campaign_offers')->restrictOnDelete();
            $table->foreignUuid('ad_payment_record_id')->unique()->constrained('ad_payment_records')->restrictOnDelete();
            $table->foreignUuid('publisher_reward_entitlement_id')->nullable()->constrained('publisher_reward_entitlements')->nullOnDelete();
            $table->unsignedSmallInteger('publisher_share_percent');
            $table->char('currency',3);
            $table->unsignedBigInteger('gross_minor');
            $table->string('status',24)->default('held');
            $table->timestamps();
        });
        Schema::create('ad_ledger_entries',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ad_ledger_batch_id')->constrained('ad_ledger_batches')->restrictOnDelete();
            $table->string('account',32);
            $table->string('direction',6);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency',3);
            $table->timestamps();
            $table->unique(['ad_ledger_batch_id','account','direction'],'ad_ledger_unique_entry');
        });
    }
    public function down():void {
        Schema::dropIfExists('ad_ledger_entries');
        Schema::dropIfExists('ad_ledger_batches');
        Schema::dropIfExists('ad_payment_records');
    }
};