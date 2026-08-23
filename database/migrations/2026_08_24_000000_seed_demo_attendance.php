<?php

use Database\Seeders\DemoAttendanceSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Puts the demo attendance in on deploy, so production gets it without
 * anyone SSHing in. All the actual work lives in DemoAttendanceSeeder.
 *
 * To seed by hand at any time:
 *   php artisan db:seed --class=DemoAttendanceSeeder
 */
return new class extends Migration {

    public function up(): void {

        if (DemoAttendanceSeeder::ready()) {
            (new DemoAttendanceSeeder)->run();
        }
    }

    public function down(): void {

        DemoAttendanceSeeder::wipe();
    }

};
