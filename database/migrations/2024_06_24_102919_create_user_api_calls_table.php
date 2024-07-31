<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserApiCallsTable extends Migration
{
    public function up()
    {
        Schema::create('user_api_calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('call_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_api_calls');
    }
}
