<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryTransaksisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('history_transaksis', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('transaksi_id')->nullable()->unsigned();
            $table->float('dibayar')->nullable();
            $table->float('sisa')->nullable();
            $table->datetime('tanggal')->nullable();
            $table->string('menu')->nullable();
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
        Schema::dropIfExists('history_transaksis');
    }
}
