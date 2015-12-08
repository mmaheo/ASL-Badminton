<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('player_one')->unsigned()->index();
            $table->foreign('player_one')->references('id')->on('players')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('player_two')->unsigned()->index()->nullable();
            $table->foreign('player_two')->references('id')->on('players')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('season_id')->unsigned()->index();
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade')->onUpdate('cascade');

            $table->boolean('simple');
            $table->boolean('double');
            $table->boolean('mixte');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('teams');
    }
}
