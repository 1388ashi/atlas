<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiftPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            //$table->string('image'); //in media library
            $table->unsignedInteger('price')->nullable(); //If null, is free
            $table->unsignedTinyInteger('order')->nullable();
            $table->boolean('status')->default(1);
            $table->string('description')->nullable();
            $table->authors();
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
        Schema::dropIfExists('gift_packages');
    }
}
