<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Miraki attendance — fed by the ZKTeco iFace950 Plus over ADMS/PUSH.
     *
     * Raw punches only. The device sends one line per finger touch and never
     * says in or out, so in/out is worked out at report time by alternating
     * the punches of each day. Nothing is summarised on write.
     *
     * Completely separate from the Lionsfort employers/attendance_events tables.
     */
    public function up(): void
    {
        // One row per punch, exactly as the device sent it.
        Schema::create('miraki', function (Blueprint $table) {
            $table->id();
            $table->string('pin');                        // device user id, e.g. "1"
            $table->dateTime('punched_at');               // device local time
            $table->unsignedTinyInteger('status')->default(0);      // device in/out flag, always 0 today
            $table->unsignedTinyInteger('verify')->nullable();      // 1 = fingerprint, 15 = face
            $table->string('device_sn');
            $table->text('raw');                          // original tab separated line
            $table->timestamps();

            // The device re-sends punches after a reconnect. This drops the repeats.
            // device_sn is part of the key so two clients can both have PIN 1.
            $table->unique(['device_sn', 'pin', 'punched_at']);
            $table->index('punched_at');
        });

        // pin -> name, refreshed from the device by /<client>/pull-users
        Schema::create('miraki_users', function (Blueprint $table) {
            $table->id();
            $table->string('pin');
            $table->string('name');
            $table->unsignedTinyInteger('privilege')->default(0);   // Pri=14 is admin
            $table->string('device_sn');
            $table->timestamps();

            $table->unique(['device_sn', 'pin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('miraki_users');
        Schema::dropIfExists('miraki');
    }
};
