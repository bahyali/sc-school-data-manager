<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('school_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('number');
            $table->string('principal_name')->nullable();
            $table->string('special_conditions_code')->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->text('address_line_3')->nullable();
            $table->string('country')->nullable();
            $table->string('telephone')->nullable();
            $table->string('fax')->nullable();
            $table->text('website')->nullable();
            $table->string('status')->nullable();

            $table->string('logo')->nullable();
            $table->string('level')->nullable();
            $table->string('program_type')->nullable();
            $table->string('region')->nullable();
            $table->string('association_membership')->nullable();
            $table->string('ossd_credits_offered')->nullable();
            $table->string('owner_business')->nullable();
            $table->string('corporation_name')->nullable();
            $table->string('corporation_establish_date')->nullable();
            $table->string('corporation_contact_name')->nullable();
            $table->string('teachers_num')->nullable();
            $table->string('oct_teachers')->nullable();
            $table->string('enrollment')->nullable();
            
            $table->string('revoked_date')->nullable();
            $table->string('closed_date')->nullable();
            $table->string('open_date')->nullable();
            
            $table->date('data_src_date')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->foreign('school_id')->references('id')->on('schools');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('school_revisions');
    }
}
