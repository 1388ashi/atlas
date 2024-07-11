<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// UNUSED
class CreateCalculateDiscountFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::raw("DROP FUNCTION IF EXISTS calculateDiscount;
CREATE FUNCTION calculateDiscount(actual_price INT, discount_type CHAR(250), discount_amount INT)
RETURNS INTEGER
DETERMINISTIC
BEGIN
    return IF((discount_type = 'flat'), (actual_price - discount_amount),
              ((actual_price - (discount_amount * actual_price / 100))));
    END;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \Illuminate\Support\Facades\DB::raw('DROP FUNCTION IF EXISTS calculateDiscount;');
    }
}
