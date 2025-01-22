<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewOnsisColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('school_revisions', function (Blueprint $table) {
            $table->text('enrollment_23_24')->after('enrollment_22_23')->nullable();
            $table->text('enrollment_24_25')->after('enrollment_23_24')->nullable();
            $table->text('enrollment_25_26')->after('enrollment_24_25')->nullable();
            
            $table->text('diploma_2023_2024')->after('diploma_2022_2023')->nullable();
            $table->text('diploma_2024_2025')->after('diploma_2023_2024')->nullable();
            $table->text('diploma_2025_2026')->after('diploma_2024_2025')->nullable();

            $table->string('owner_email')->after('owner_business')->nullable();
            $table->string('corporation_number')->after('corporation_contact_name')->nullable();
            $table->string('corporation_email')->after('corporation_number')->nullable();
            $table->string('principal_email')->after('principal_last_name')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('school_revisions', function (Blueprint $table) {
            $table->dropColumn('enrollment_23_24');
            $table->dropColumn('enrollment_24_25');
            $table->dropColumn('enrollment_25_26');
            $table->dropColumn('diploma_2023_2024');
            $table->dropColumn('diploma_2024_2025');
            $table->dropColumn('diploma_2025_2026');
            $table->dropColumn('owner_email');
            $table->dropColumn('corporation_number');
            $table->dropColumn('corporation_email');
            $table->dropColumn('principal_email');
        });
    }
}
