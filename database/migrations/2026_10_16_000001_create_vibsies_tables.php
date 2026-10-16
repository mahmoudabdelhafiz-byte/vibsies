<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('users',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->timestamp('email_verified_at')->nullable();$t->string('password');$t->string('locale',8)->default('en');$t->rememberToken();$t->timestamps();});
  Schema::create('organizations',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignId('owner_id')->constrained('users');$t->string('name');$t->char('country_code',2);$t->timestamps();});
  Schema::create('organization_user',function(Blueprint $t){$t->foreignId('user_id')->constrained();$t->foreignUuid('organization_id')->constrained();$t->string('role')->default('member');$t->timestamps();$t->primary(['user_id','organization_id']);});
  Schema::create('creator_profiles',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignId('user_id')->unique()->constrained();$t->string('display_name');$t->string('slug')->unique();$t->string('category');$t->text('bio')->nullable();$t->char('country_code',2)->default('EG');$t->string('status')->default('pending');$t->timestamps();});
  Schema::create('creator_packages',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignUuid('creator_profile_id')->constrained();$t->string('title');$t->text('description')->nullable();$t->unsignedBigInteger('price_minor');$t->char('currency',3)->default('EGP');$t->unsignedSmallInteger('turnaround_days')->default(7);$t->unsignedSmallInteger('revision_limit')->default(1);$t->boolean('is_active')->default(false);$t->timestamps();});
  Schema::create('bookings',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignId('customer_id')->constrained('users');$t->foreignUuid('creator_profile_id')->constrained();$t->foreignUuid('creator_package_id')->constrained();$t->foreignUuid('organization_id')->nullable()->constrained();$t->text('brief');$t->unsignedBigInteger('price_minor');$t->unsignedBigInteger('fee_minor');$t->char('currency',3);$t->string('status')->default('requested');$t->timestamps();});
  Schema::create('agreements',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignUuid('booking_id')->unique()->constrained();$t->jsonb('terms');$t->char('terms_sha256',64);$t->timestampTz('customer_accepted_at')->nullable();$t->timestampTz('creator_accepted_at')->nullable();$t->timestamps();});
  Schema::create('sessions',function(Blueprint $t){$t->string('id')->primary();$t->foreignId('user_id')->nullable()->index();$t->string('ip_address',45)->nullable();$t->text('user_agent')->nullable();$t->longText('payload');$t->integer('last_activity')->index();});
  Schema::create('cache',function(Blueprint $t){$t->string('key')->primary();$t->mediumText('value');$t->integer('expiration');});
  Schema::create('cache_locks',function(Blueprint $t){$t->string('key')->primary();$t->string('owner');$t->integer('expiration');});
  Schema::create('jobs',function(Blueprint $t){$t->id();$t->string('queue')->index();$t->longText('payload');$t->unsignedTinyInteger('attempts');$t->unsignedInteger('reserved_at')->nullable();$t->unsignedInteger('available_at');$t->unsignedInteger('created_at');});
  Schema::create('job_batches',function(Blueprint $t){$t->string('id')->primary();$t->string('name');$t->integer('total_jobs');$t->integer('pending_jobs');$t->integer('failed_jobs');$t->longText('failed_job_ids');$t->mediumText('options')->nullable();$t->integer('cancelled_at')->nullable();$t->integer('created_at');$t->integer('finished_at')->nullable();});
  Schema::create('failed_jobs',function(Blueprint $t){$t->id();$t->string('uuid')->unique();$t->text('connection');$t->text('queue');$t->longText('payload');$t->longText('exception');$t->timestamp('failed_at')->useCurrent();});
 }
 public function down():void{foreach(['failed_jobs','job_batches','jobs','cache_locks','cache','sessions','agreements','bookings','creator_packages','creator_profiles','organization_user','organizations','users'] as $name)Schema::dropIfExists($name);}
};
