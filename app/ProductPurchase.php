<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    protected $table = 'product_purchases';
    protected $fillable =[

        "purchase_id", "product_id", "product_batch_id", "variant_id", "qty", "recieved", "purchase_unit_id", "net_unit_cost", "purchase_unit_id_2", "purchase_unit_value_2", "purchase_unit_id_3", "purchase_unit_value_3", "discount", "tax_rate", "tax", "total"
    ];
}
