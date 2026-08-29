<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Initial schema for the whole property-manager domain, created in one
 * migration rather than one table per file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document');
            $table->string('email');
            $table->string('phone');
            $table->timestamps();
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('type');
            $table->string('status');
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('document');
            $table->string('email');
            $table->string('whatsapp');
            $table->string('status');
            $table->unsignedInteger('resident_count')->nullable();
            $table->timestamps();
        });

        Schema::create('receivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('document');
            $table->string('email');
            $table->string('mercado_pago_account')->nullable();
            $table->boolean('active')->default(true);
            $table->string('mp_user_id')->nullable();
            $table->text('mp_access_token')->nullable();
            $table->text('mp_refresh_token')->nullable();
            $table->timestamp('mp_token_expires_at')->nullable();
            $table->timestamp('mp_connected_at')->nullable();
            $table->boolean('mp_live_mode')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('content');
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('receivers')->cascadeOnDelete();
            $table->decimal('monthly_rent', 12, 2);
            $table->unsignedTinyInteger('due_day');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->decimal('fine_rate', 8, 4);
            $table->decimal('monthly_interest_rate', 8, 4);
            $table->unsignedInteger('grace_days')->default(0);
            $table->string('status');
            $table->foreignId('template_id')->nullable()->constrained('contract_templates')->nullOnDelete();
            $table->longText('contract_text')->nullable();
            $table->string('signature_status')->default('not_generated');
            $table->string('signed_document_path')->nullable();
            $table->string('signed_file_name')->nullable();
            $table->timestamp('signed_uploaded_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->string('generated_document_path')->nullable();
            $table->timestamp('generated_document_updated_at')->nullable();
            $table->timestamp('owner_signed_at')->nullable();
            $table->timestamp('expiring_reminder_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_witnesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('receivers')->cascadeOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('receivers')->cascadeOnDelete();
            $table->string('reference');
            $table->date('due_date');
            $table->decimal('original_amount', 12, 2);
            $table->string('status');
            $table->string('mercado_pago_order_id')->nullable()->index();
            $table->string('mercado_pago_transaction_id')->nullable();
            $table->string('payment_url')->nullable();
            $table->text('pix_qr_code')->nullable();
            $table->longText('pix_qr_code_base64')->nullable();
            $table->timestamp('pix_expires_at')->nullable();
            $table->decimal('rateio_amount', 12, 2)->nullable();
            $table->string('last_reminder_event')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'reference']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained('charges')->cascadeOnDelete();
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('net_amount', 12, 2)->nullable();
            $table->decimal('fees', 12, 2)->nullable();
            $table->string('method');
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->string('external_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('contract_inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('storage_path');
            $table->string('file_name');
            $table->string('content_type');
            $table->string('caption')->nullable();
            $table->string('room')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('contract_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->text('description');
            $table->string('status')->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_occurrence_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occurrence_id')->constrained('contract_occurrences')->cascadeOnDelete();
            $table->string('storage_path');
            $table->string('file_name');
            $table->string('content_type');
            $table->timestamps();
        });

        Schema::create('rateios', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('outro');
            $table->string('description')->nullable();
            $table->string('reference');
            $table->decimal('total_amount', 12, 2);
            $table->string('invoice_path')->nullable();
            $table->string('invoice_content_type')->nullable();
            $table->string('invoice_file_name')->nullable();
            $table->string('split_mode')->default('residents');
            $table->timestamps();
        });

        Schema::create('rateio_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rateio_id')->constrained('rateios')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->foreignId('charge_id')->nullable()->constrained('charges')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rateio_allocations');
        Schema::dropIfExists('rateios');
        Schema::dropIfExists('contract_occurrence_photos');
        Schema::dropIfExists('contract_occurrences');
        Schema::dropIfExists('contract_inspection_photos');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('charges');
        Schema::dropIfExists('contract_witnesses');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('receivers');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('owners');
    }
};
