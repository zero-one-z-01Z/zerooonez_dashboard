<?php

use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password')->nullable();
            $table->dateTime('last_login')->nullable();
            $table->boolean('super')->default(false);
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->boolean('active')->default(true);
            $table->integer('tickets_number')->default(0);
            $table->boolean('admin_tickets')->default(false);
            $table->unsignedBigInteger('role_id');
            $table->string('customer_id')->nullable();
            $table->foreignId('admin_company_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('admin_customer_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->integer('recommendations_number')->default(0);
            $table->timestamps();
        });
        Admin::create([
            'name'=>env('APP_NAME'),
            "email"=>'admin@admin.com',
            "phone"=>'555555555',
            "super"=>true,
            "role_id"=>0,
            "password"=>Hash::make('[REDACTED_SOURCE_DEFAULT]')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
