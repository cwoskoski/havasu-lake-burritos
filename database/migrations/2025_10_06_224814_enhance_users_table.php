<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->boolean('sms_notifications')->default(true)->after('phone_verified_at');
            $table->boolean('marketing_sms')->default(false)->after('sms_notifications');
            $table->string('preferred_name', 50)->nullable()->after('name');
            $table->json('preferences')->nullable()->after('marketing_sms');

            $table->index('phone');
            $table->index('phone_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropIndex(['phone_verified_at']);
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'sms_notifications',
                'marketing_sms',
                'preferred_name',
                'preferences'
            ]);
        });
    }
};
