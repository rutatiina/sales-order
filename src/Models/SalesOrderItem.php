<?php

namespace Rutatiina\SalesOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Scopes\TenantIdScope;

class SalesOrderItem extends Model
{
    use LogsActivity;

    protected static $logName = 'TxnItem';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_sales_order_items';

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

    public function sales_order()
    {
        return $this->belongsTo('Rutatiina\SalesOrder\Models\SalesOrder', 'sales_order_id', 'id');
    }

    public function taxes()
    {
        return $this->hasMany('Rutatiina\SalesOrder\Models\SalesOrderItemTax', 'sales_order_item_id', 'id');
    }

}
