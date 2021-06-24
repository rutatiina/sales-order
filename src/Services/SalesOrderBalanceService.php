<?php

namespace Rutatiina\SalesOrder\Services;

use Rutatiina\SalesOrder\Models\SalesOrderBalance;

trait SalesOrderBalanceService
{
    public static function update($txn, $reverse = false)
    {
        if (strtolower($txn['status']) == 'draft')
        {
            //cannot update balances for drafts
            return false;
        }

        if (isset($txn['balances_where_updated']) && $txn['balances_where_updated'])
        {
            //cannot update balances for task already completed
            return false;
        }

        //Defaults
        $total = $txn['total'];

        if ($reverse)
        {
            $total = $txn['total'] * -1;
        }

        if (empty($txn['contact_id']))
        {
            return true;
        }

        $currencies = [];
        $currencies[$txn['base_currency']] = $txn['base_currency'];
        $currencies[$txn['quote_currency']] = $txn['quote_currency'];

        foreach ($currencies as $currency)
        {
            if ($currency == $txn['base_currency'])
            {
                //Do nothing because the values are in the base currency
            }
            else
            {
                $total = $txn['total'] * $txn['exchange_rate'];
            }

            //1. find the last record
            $contactBalance = SalesOrderBalance::where('date', '<=', $txn['date'])
                //->where('tenant_id', $txn['tenant_id']) //TenantIdScope
                ->where('currency', $currency)
                ->where('contact_id', $txn['contact_id'])
                ->orderBy('date', 'DESC')
                ->first();

            //var_dump($contactBalance->num_rows()); exit;

            switch ($contactBalance)
            {
                case null:

                    //create a new balance record
                    $contactBalanceInsert = new SalesOrderBalance;
                    $contactBalanceInsert->tenant_id = $txn['tenant_id'];
                    $contactBalanceInsert->contact_id = $txn['contact_id'];
                    $contactBalanceInsert->date = $txn['date'];
                    $contactBalanceInsert->currency = $currency;
                    $contactBalanceInsert->balance = 0;
                    $contactBalanceInsert->save();

                    break;

                default:

                    //create a new row with the last balances
                    if ($txn['date'] == $contactBalance->date)
                    {
                        //do nothing because the records for this dates balances already exists
                    }
                    else
                    {
                        $contactBalanceInsert = new SalesOrderBalance;
                        $contactBalanceInsert->tenant_id = $txn['tenant_id'];
                        $contactBalanceInsert->contact_id = $txn['contact_id'];
                        $contactBalanceInsert->date = $txn['date'];
                        $contactBalanceInsert->currency = $currency;
                        $contactBalanceInsert->balance = $contactBalance->balance;
                        $contactBalanceInsert->save();
                    }

                    break;
            }

            SalesOrderBalance::where('date', '>=', $txn['date'])
                ->where('currency', $currency)
                ->where('contact_id', $txn['contact_id'])
                ->increment('balance', $total);

        }

        $txn->status = 'approved';
        $txn->balances_where_updated = 1;
        $txn->save();

        return true;

    }

}
