<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RgSalesOrderRenameContactNotesToMemo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection('tenant')->hasColumn('rg_sales_orders', 'contact_notes'))
        {
            Schema::connection('tenant')->table('rg_sales_orders', function(Blueprint $table) {
                $table->renameColumn('contact_notes', 'memo');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::connection('tenant')->hasColumn('rg_sales_orders', 'memo'))
        {
            Schema::connection('tenant')->table('rg_sales_orders', function(Blueprint $table) {
                $table->renameColumn('memo', 'contact_notes');
            });
        }
    }
}
