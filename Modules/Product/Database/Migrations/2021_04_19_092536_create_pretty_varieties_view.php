<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// UNUSED
class CreatePrettyVarietiesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        \Illuminate\Support\Facades\DB::statement('DROP FUNCTION IF EXISTS calculateDiscount');
//        \Illuminate\Support\Facades\DB::statement("
//        CREATE FUNCTION calculateDiscount(actual_price INT, discount_type CHAR(250), discount_amount INT)
//    RETURNS INTEGER
//    DETERMINISTIC
//BEGIN
//    return IF((discount_type = 'flat'), (actual_price - discount_amount),
//              ((actual_price - (discount_amount * actual_price / 100))));
//END");
//        \Illuminate\Support\Facades\DB::statement("
//        CREATE OR REPLACE VIEW `pretty_varieties` as
//    (SELECT v.*,
//    FLOOR(
//        (CASE WHEN (f.id IS NOT NULL AND f.start_date > now() AND f.end_date < now() AND f.status = 1) THEN
//    calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//        fp.discount_type, fp.discount)
//    WHEN v.discount_type IS NOT NULL THEN
//    calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//        v.discount_type, v.discount)
//    WHEN p.discount_type IS NOT NULL THEN
//    calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//        p.discount_type, p.discount)
//    ELSE CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END END)
//        ) as final_price,
//
//    CASE WHEN (f.id IS NOT NULL AND f.start_date > now() AND f.end_date < now() AND f.status = 1)
//            THEN 'flash'
//        WHEN v.discount_type IS NOT NULL
//            THEN 'variety'
//        WHEN p.discount_type IS NOT NULL
//            THEN 'product'
//        ELSE 'none' END
//        as applied_discount_type,
//   FLOOR(IF(v.price IS NOT NULL, v.price -  FLOOR(
//           (CASE WHEN (f.id IS NOT NULL AND f.start_date > now() AND f.end_date < now() AND f.status = 1) THEN
//                     calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//                         fp.discount_type, fp.discount)
//                 WHEN v.discount_type IS NOT NULL THEN
//                     calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//                         v.discount_type, v.discount)
//                 WHEN p.discount_type IS NOT NULL THEN
//                     calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//                         p.discount_type, p.discount)
//                 ELSE CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END END)
//       ), p.unit_price - FLOOR(
//           (CASE WHEN (f.id IS NOT NULL AND f.start_date > now() AND f.end_date < now() AND f.status = 1) THEN
//                     calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//                                       fp.discount_type, fp.discount)
//                 WHEN v.discount_type IS NOT NULL THEN
//                     calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//                                       v.discount_type, v.discount)
//                 WHEN p.discount_type IS NOT NULL THEN
//                     calculateDiscount(CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END,
//                                       p.discount_type, p.discount)
//                 ELSE CASE WHEN (v.price IS NOT NULL) THEN v.price WHEN (p.unit_price IS NOT NULL ) THEN p.unit_price END END)
//       )))
//        as applied_discount_amount
//FROM varieties v JOIN products p ON v.product_id = p.id
//LEFT JOIN flash_product fp ON p.id = fp.product_id
//LEFT JOIN flashes f on fp.flash_id = f.id);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \Illuminate\Support\Facades\DB::statement('DROP VIEW IF EXISTS pretty_varieties');
    }
}
