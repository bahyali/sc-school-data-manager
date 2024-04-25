<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnrollment2223FieldToSchoolRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('school_revisions', function (Blueprint $table) {
            $table->string('enrollment_22_23')->after('enrollment_21_22')->nullable();
            $table->string('diploma_2022_2023')->after('diploma_2021_2022')->nullable();
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
            $table->dropColumn('enrollment_22_23');
            $table->dropColumn('diploma_2022_2023');
        });
    }
}
