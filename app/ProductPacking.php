<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductPacking extends Model
{
    protected $table = 'product_packings';
    protected $fillable =[
        "purchase_id", "washing_id", "packing_id", "product_id", "product_batch_id", "variant_id", "qty", "recieved", "wastage", "purchase_unit_id", "net_unit_cost", "purchase_unit_id_2", "purchase_unit_value_2", "purchase_unit_id_3", "purchase_unit_value_3", "discount", "tax_rate", "tax", "total"
    ];
}
