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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            
            // TOTP (Two-Factor Authentication)
            $table->string('totp_secret', 32)->nullable();
            $table->boolean('totp_enabled')->default(false);
            $table->text('totp_backup_codes')->nullable();
            
            // Email verification
            $table->boolean('email_verified')->default(false);
            $table->string('email_verification_token', 64)->nullable();
            $table->timestamp('email_verification_expires')->nullable();
            
            // Profile
            $table->string('avatar_url', 500)->nullable();
            $table->enum('registration_method', ['local', 'google', 'facebook'])->default('local');
            $table->string('preferred_language', 10)->default('nl');
            
            // Notification preferences
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('email_notifications')->default(true);
            $table->boolean('push_notifications')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('username');
            $table->index('email');
            $table->index('is_active');
            $table->index('email_verified');
            $table->index('email_verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}; 