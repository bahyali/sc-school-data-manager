<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDataChangeMigrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('data_changes', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('column');
            $table->timestamps();
        });

        Schema::create('data_change_values', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('data_change_id');
            $table->foreign('data_change_id')->references('id')->on('data_changes');

            $table->unsignedBigInteger('revision_id');
            $table->foreign('revision_id')->references('id')->on('school_revisions');

            $table->string('value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('data_change_values');
        Schema::drop('data_changes');
    }
}
