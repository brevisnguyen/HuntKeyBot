<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id');
            $table->boolean('is_start')->default(false);
            $table->boolean('is_end')->default(false);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('stop_time')->nullable();

            $table->foreign('chat_id')->references('id')->on('chats')->onUpdate('cascade')->onDelete('cascade');
            $table->index('chat_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_shifts');
    }
}
