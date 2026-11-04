<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ad_provider_events',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider',40);
            $table->string('event_id',160);
            $table->string('event_type',40);
            $table->foreignUuid('ad_payment_record_id')->nullable()->constrained('ad_payment_records')->restrictOnDelete();
            $table->string('status',30)->default('received');
            $table->text('processing_note')->nullable();
            $table->timestamps();
            $table->unique(['provider','event_id']);
        });
        Schema::create('ad_finance_reconciliation_runs',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('as_of');
            $table->unsignedInteger('checked_batches')->default(0);
            $table->unsignedInteger('exceptions')->default(0);
            $table->string('status',30)->default('incomplete');
            $table->timestamps();
        });
        Schema::create('ad_finance_reconciliation_exceptions',function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('run_id')->constrained('ad_finance_reconciliation_runs')->cascadeOnDelete();
            $table->foreignUuid('ad_ledger_batch_id')->nullable()->constrained('ad_ledger_batches')->restrictOnDelete();
            $table->string('code',60);
            $table->text('details');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('ad_finance_reconciliation_exceptions');
        Schema::dropIfExists('ad_finance_reconciliation_runs');
        Schema::dropIfExists('ad_provider_events');
    }
};