<?php

namespace Rutatiina\SalesOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrderItemTax extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static $logName = 'TxnItem';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_sales_order_item_taxes';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);
    }

    public function tax()
    {
        return $this->hasOne('Rutatiina\Tax\Models\Tax', 'code', 'tax_code');
    }

    public function sales_order()
    {
        return $this->belongsTo('Rutatiina\SalesOrder\Models\SalesOrder', 'sales_order_id', 'id');
    }

    public function sales_order_item()
    {
        return $this->belongsTo('Rutatiina\SalesOrder\Models\SalesOrderItem', 'sales_order_item_id', 'id');
    }

}
