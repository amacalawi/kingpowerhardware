<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('delivery_id')->unsigned();
            $table->integer('item_id')->unsigned();
            $table->string('uom', 20)->nullable();
            $table->boolean('is_special')->default(0);
            $table->double('srp')->default(0);
            $table->double('quantity')->default(0);
            $table->double('discount1')->nullable();
            $table->double('discount2')->nullable();
            $table->double('plus')->nullable();
            $table->double('total_amount')->default(0);
            $table->double('posted_quantity')->default(0);
            $table->string('status', 20)->default('prepared');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('created_by')->unsigned();
            $table->timestamp('updated_at')->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->boolean('is_active')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_lines');
    }
}
