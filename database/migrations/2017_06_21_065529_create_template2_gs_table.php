<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemplate2GsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_2G', function (Blueprint $table) {
            $table->increments('id');
            $table->text('templateName');
            $table->string('elementId', 500);
            $table->string('description', 500);
            $table->string('user', 255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('template_2G');
    }
}
