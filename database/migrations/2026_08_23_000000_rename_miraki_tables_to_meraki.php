<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The company is spelled Meraki, not Miraki. Rename the two tables and keep
 * every row — a rename does not touch the data.
 *
 * Guarded both ways so it is safe to run on a database that is already
 * renamed, and on a brand new one.
 */
return new class extends Migration {

    public function up(): void {

        foreach (['miraki' => 'meraki', 'miraki_users' => 'meraki_users'] as $old => $new) {
            if (Schema::hasTable($old) && ! Schema::hasTable($new)) {
                Schema::rename($old, $new);
            }
        }
    }

    public function down(): void {

        foreach (['meraki' => 'miraki', 'meraki_users' => 'miraki_users'] as $new => $old) {
            if (Schema::hasTable($new) && ! Schema::hasTable($old)) {
                Schema::rename($new, $old);
            }
        }
    }

};
