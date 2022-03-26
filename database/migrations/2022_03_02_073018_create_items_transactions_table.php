<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('item_id')->unsigned();
            $table->integer('branch_id')->unsigned();
            $table->string('transaction', 10);
            $table->double('based_quantity')->default(0);
            $table->double('issued_quantity')->default(0);
            $table->double('left_quantity')->default(0);
            $table->integer('issued_by')->nullable();
            $table->integer('received_by')->nullable();
            $table->double('srp')->default(0);
            $table->double('total_amount')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('created_by')->unsigned();
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
        Schema::dropIfExists('items_transactions');
    }
}
