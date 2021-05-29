<?php

namespace Rutatiina\SalesOrder\Http\Controllers;

use Rutatiina\SalesOrder\Models\Setting;
use Rutatiina\SalesOrder\Services\SalesOrderService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\SalesOrder\Models\SalesOrder;
use Rutatiina\Contact\Traits\ContactTrait;
use Yajra\DataTables\Facades\DataTables;

use Rutatiina\SalesOrder\Traits\Item as TxnItem;

class SalesOrderController extends Controller
{
    use ContactTrait;
    use TxnItem; // >> get the item attributes template << !!important

    public function __construct()
    {
        $this->middleware('permission:sales-orders.view');
		$this->middleware('permission:sales-orders.create', ['only' => ['create','store']]);
		$this->middleware('permission:sales-orders.update', ['only' => ['edit','update']]);
		$this->middleware('permission:sales-orders.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $query = SalesOrder::query();

        if ($request->contact)
        {
            $query->where(function($q) use ($request) {
                $q->where('debit_contact_id', $request->contact);
                $q->orWhere('credit_contact_id', $request->contact);
            });
        }

        $txns = $query->latest()->paginate($request->input('per_page', 20));

        return [
            'tableData' => $txns
        ];
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new SalesOrder())->rgGetAttributes();

        $txnAttributes['number'] = SalesOrderService::nextNumber();
        $txnAttributes['status'] = 'approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['isRecurring'] = false;
        $txnAttributes['recurring'] = [
            'date_range' => [],
            'day_of_month' => '*',
            'month' => '*',
            'day_of_week' => '*',
        ];
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [$this->itemCreate()];

        unset($txnAttributes['txn_entree_id']); //!important
        unset($txnAttributes['txn_type_id']); //!important
        unset($txnAttributes['debit_contact_id']); //!important
        unset($txnAttributes['credit_contact_id']); //!important

        $data = [
            'pageTitle' => 'Create Sales Order', #required
            'pageAction' => 'Create', #required
            'txnUrlStore' => '/sales-orders', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }

    public function store(Request $request)
	{
        //print_r($request->all()); exit;

        $storeService = SalesOrderService::store($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => SalesOrderService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Sales Order saved'],
            'number' => 0,
            'callback' => URL::route('sales-orders.show', [$storeService->id], false)
        ];
    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txn = SalesOrder::findOrFail($id);
        $txn->load('contact', 'financial_account', 'items.taxes');
        $txn->setAppends([
            'taxes',
            'number_string',
            'total_in_words',
        ]);

        return $txn->toArray();
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txnAttributes = SalesOrderService::edit($id);

        $data = [
            'pageTitle' => 'Edit Sales order', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/sales-orders/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }

    public function update(Request $request)
    {
        //print_r($request->all()); exit;

        $storeService = SalesOrderService::update($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => SalesOrderService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Sales order updated'],
            'number' => 0,
            'callback' => URL::route('sales-orders.show', [$storeService->id], false)
        ];
    }

    public function destroy($id)
	{
        $destroy = SalesOrderService::destroy($id);

        if ($destroy)
        {
            return [
                'status' => true,
                'messages' => ['Sales order deleted'],
                'callback' => URL::route('sales-orders.index', [], false)
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => SalesOrderService::$errors
            ];
        }
	}

	#-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $approve = SalesOrderService::approve($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => SalesOrderService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Sales order approved'],
        ];
    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txnAttributes = SalesOrderService::copy($id);

        $data = [
            'pageTitle' => 'Copy Sales order', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/sales-orders', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }

    public function process()
    {
        //
    }

    public function datatables(Request $request)
	{

        $txns = Transaction::setRoute('show', route('accounting.sales.sales-orders.show', '_id_'))
			->setRoute('edit', route('accounting.sales.sales-orders.edit', '_id_'))
			->setSortBy($request->sort_by)
			->paginate(false);

        return Datatables::of($txns)->make(true);
    }

    public function exportToExcel(Request $request) {

        $txns = collect([]);

        $txns->push([
            'DATE',
            'DOCUMENT#',
            'REFERENCE',
            'CUSTOMER',
            'STATUS',
            'EXPIRY DATE',
            'TOTAL',
            ' ', //Currency
        ]);

        foreach (array_reverse($request->ids) as $id) {
            $txn = Transaction::transaction($id);

            $txns->push([
                $txn->date,
                $txn->number,
                $txn->reference,
                $txn->contact_name,
                $txn->status,
                $txn->expiry_date,
                $txn->total,
                $txn->base_currency,
            ]);
        }

        $export = $txns->downloadExcel(
            'maccounts-sales-orders-export-'.date('Y-m-d-H-m-s').'.xlsx',
            null,
            false
        );

        //$books->load('author', 'publisher'); //of no use

        return $export;
    }

}
