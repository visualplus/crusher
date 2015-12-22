<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrushSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crush_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('userno');
            $table->string('rule', 10);
            $table->integer('progressed_that');
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
        Schema::drop('crush_schedules');
    }
}
