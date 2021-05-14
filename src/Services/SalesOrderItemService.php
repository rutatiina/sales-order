<?php

namespace Rutatiina\SalesOrder\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Rutatiina\SalesOrder\Models\SalesOrder;
use Rutatiina\SalesOrder\Models\SalesOrderItem;
use Rutatiina\SalesOrder\Models\SalesOrderItemTax;

class SalesOrderItemService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        //print_r($data['items']); exit;

        //Save the items >> $data['items']
        foreach ($data['items'] as &$item)
        {
            $item['sales_order_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = SalesOrderItem::create($item);

            foreach ($itemTaxes as $tax)
            {
                //save the taxes attached to the item
                $itemTax = new SalesOrderItemTax;
                $itemTax->tenant_id = $item['tenant_id'];
                $itemTax->sales_order_id = $item['sales_order_id'];
                $itemTax->sales_order_item_id = $itemModel->id;
                $itemTax->tax_code = $tax['code'];
                $itemTax->amount = $tax['total'];
                $itemTax->inclusive = $tax['inclusive'];
                $itemTax->exclusive = $tax['exclusive'];
                $itemTax->save();
            }
            unset($tax);
        }
        unset($item);

    }

}
