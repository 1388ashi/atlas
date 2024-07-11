<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGiftPackageIdOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('gift_package_id')
                ->nullable();
                $table->unsignedBigInteger('gift_package_price')->nullable()->default(0);

            $table->foreign('gift_package_id')
                ->references('id')
                ->on('gift_packages')
                ->nullOnDelete();
   
        });
        DB::statement(
            <<<END
                     CREATE OR REPLACE VIEW order_reports_view AS
            (SELECT o.*,
                   (SUM(oi.amount * oi.quantity) + COALESCE(o12.total, 0) + COALESCE(o.gift_package_price, 0) + o.shipping_amount - o.discount_amount) as total,
                   (SUM(oi.discount_amount * oi.quantity) + COALESCE(o12.not_coupon_discount_amount, 0)) as not_coupon_discount_amount,
                   CONCAT(GROUP_CONCAT('"', JSON_VALUE(oi.extra, '$.color.id'), '-', oi.quantity, '"'), ',', COALESCE(o12.color_ids, ''))      AS color_ids,
                   CONCAT(GROUP_CONCAT(CONCAT(JSON_EXTRACT(oi.extra, '$.attributes[*].name'), '||',
                                              JSON_EXTRACT(oi.extra, '$.attributes[*].value'), '---', oi.quantity) SEPARATOR '!#!'), '!#!',
                          COALESCE(o12.attribute_ids, ''))                                                         AS attribute_ids,
                   (SUM(oi.quantity) + COALESCE(o12.order_items_count, 0))                                         as order_items_count,
                   (COUNT(oi.id) + COALESCE(o12.order_items_unique_count, 0))                                         as order_items_unique_count,
                   (CONCAT(GROUP_CONCAT(CONCAT('"', oi.product_id, '-', oi.quantity, '"')), ','
                       , COALESCE(o12.product_ids, '')))                                                           as product_ids
            FROM orders o
                     LEFT JOIN order_items oi ON o.id = oi.order_id AND oi.status = 1
                     LEFT JOIN (SELECT o2.reserved_id                                                             as reserved_id,
                                       (SUM(oi2.amount * oi2.quantity) + o2.shipping_amount - o2.discount_amount) as total,
                                       (SUM(oi2.discount_amount * oi2.quantity)) as not_coupon_discount_amount,
                                       SUM(oi2.quantity)                                                          as order_items_count,
                                       COUNT(oi2.id)                                                          as order_items_unique_count,
                                       GROUP_CONCAT('"', JSON_VALUE(oi2.extra, '$.color.id'), '-' , oi2.quantity, '"')                          AS color_ids,
                                       GROUP_CONCAT(CONCAT(JSON_EXTRACT(oi2.extra, '$.attributes[*].name'), '||',
                                                           JSON_EXTRACT(oi2.extra, '$.attributes[*].value'), '---', oi2.quantity) SEPARATOR '!#!')     AS attribute_ids,
                                       GROUP_CONCAT(CONCAT('"', oi2.product_id, '-', oi2.quantity, '"') SEPARATOR ',')      as product_ids
                                FROM orders o2
                                         INNER JOIN order_items oi2
                                                    ON o2.id = oi2.order_id AND oi2.status = 1
                                WHERE o2.status = 'reserved'
                                GROUP BY o2.reserved_id) o12 ON o12.reserved_id = o.id
            WHERE o.reserved_id IS NULL
            GROUP BY o.id
            )
            END
                    );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
           
            $table->dropForeign(['gift_package_id']);
            $table->dropColumn('gift_package_id');
            $table->dropColumn('gift_package_price');
        });
    }
}
