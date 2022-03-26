<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('branch_id')->unsigned();
            $table->integer('supplier_id')->unsigned();
            $table->integer('payment_terms_id')->unsigned();
            $table->integer('purchase_order_type_id')->unsigned();
            $table->string('po_no', 40);
            $table->string('contact_no', 40)->nullable();
            $table->date('due_date')->nullable();
            $table->text('delivery_place')->nullable();
            $table->text('remarks')->nullable();
            $table->double('total_amount')->default(0);
            $table->string('status', 20)->default('open');
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
        Schema::dropIfExists('purchase_orders');
    }
}
