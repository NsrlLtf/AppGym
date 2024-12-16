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
        Schema::create('members', function (Blueprint $table) {
            // Identifikasi Dasar
            $table->id();
            $table->string('name'); // Nama lengkap member
            $table->string('email')->unique(); // Email unik
            $table->string('phone')->nullable(); // Nomor telepon (opsional)
            $table->string('password'); // Password terenkripsi

            // Informasi Kartu Elektronik
            $table->string('rfid_card_number')->unique(); // Nomor kartu RFID unik
            
            // Tipe Keanggotaan
            $table->enum('membership_type', ['bronze', 'platinum', 'gold'])
                  ->default('bronze'); // Tipe keanggotaan
            
            // Periode Keanggotaan
            $table->date('membership_start_date'); // Tanggal mulai keanggotaan
            $table->date('membership_end_date'); // Tanggal berakhir keanggotaan
            
            // Status Keanggotaan
            $table->enum('status', ['active', 'inactive', 'expired'])
                  ->default('active'); // Status member
            
            // Informasi Tambahan
            $table->date('last_check_in')->nullable(); // Terakhir check-in
            $table->integer('total_check_ins')->default(0); // Total check-in
            
            // Foreign Key (Opsional)
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->foreign('registered_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // Admin yang mendaftarkan
            // Update Member Paket
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->foreign('last_updated_by')->references('id')->on('users');

            // Metadata
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken(); // Token untuk "remember me"
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
