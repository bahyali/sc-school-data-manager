<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToSchoolRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('school_revisions', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->string('grade_range')->nullable();
            $table->string('semester_type')->nullable();
            $table->string('language_of_instruction')->nullable();
            $table->string('enrollment_21_22')->nullable();
            $table->string('ossd_continuous_intake')->nullable();
            $table->string('noi_last_date_submission')->nullable();
            $table->string('noi_status_description')->nullable();
            $table->string('ownership_type')->nullable();
            $table->string('cra_bn')->nullable();

            $table->string('diploma_2014_2015')->nullable();
            $table->string('diploma_2015_2016')->nullable();
            $table->string('diploma_2016_2017')->nullable();
            $table->string('diploma_2017_2018')->nullable();
            $table->string('diploma_2018_2019')->nullable();
            $table->string('diploma_2019_2020')->nullable();
            $table->string('diploma_2020_2021')->nullable();
            $table->string('diploma_2021_2022')->nullable();

            $table->string('po_box')->nullable();
            $table->string('suite')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('province')->nullable();

            $table->string('principal_last_name')->nullable();
            $table->string('principal_qualification')->nullable();
            $table->string('principal_qualification_other')->nullable();
            $table->string('principal_start_date')->nullable();

            $table->string('affiliation')->nullable();
            $table->string('association_other')->nullable();

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
            //
        });
    }
}
