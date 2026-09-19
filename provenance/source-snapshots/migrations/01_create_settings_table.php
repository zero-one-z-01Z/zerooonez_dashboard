<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->double('delivery_km_fees')->default(0);
            $table->double('scanner_price')->default(0);
            $table->double('insurance_price')->default(0);
            $table->integer('app_percentage')->default(5);
            $table->double('admin_fees')->default(0);
            $table->double('transferred_fees')->default(250);
            $table->double('tax_percentage')->default(15);

            $table->double('gift_value')->default(0);
            $table->double('register_gift')->default(0);
            $table->double('update_profile_gift')->default(0);
            $table->double('bill_paid_gift')->default(0);
            $table->enum('scanner_auto',['auto','manually'])->default('auto');

            $table->integer('scanner_range')->default(10);
            $table->integer('auto_cancel_min_auction')->default(10);
            $table->string('iban')->default('[REDACTED_SOURCE_DEFAULT]');
            $table->string('bank_name')->default('[REDACTED_SOURCE_DEFAULT]');
            $table->text('terms_ar')->nullable();
            $table->text('terms_en')->nullable();
            $table->text('about_ar')->nullable();
            $table->text('about_en')->nullable();
            $table->text('privacy_ar')->nullable();
            $table->text('privacy_en')->nullable();
            $table->text('insurance_ar')->nullable();
            $table->text('insurance_en')->nullable();
            $table->text('refund_ar')->nullable();
            $table->text('refund_en')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->integer('user_version')->default(1);
            $table->integer('user_ios_version')->default(1);
            $table->boolean('must_update_user')->default(false);
            $table->boolean('must_update_user_ios')->default(false);
            $table->timestamps();
        });
        \App\Models\Setting::create([

        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
