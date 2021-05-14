<?php

namespace Rutatiina\SalesOrder\Http\Controllers;

use Rutatiina\SalesOrder\Models\Setting;
use URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\View;
use Rutatiina\SalesOrder\Models\SalesOrder;
use Rutatiina\Item\Traits\ItemsSelect2DataTrait;
use Rutatiina\Contact\Traits\ContactTrait;
use Yajra\DataTables\Facades\DataTables;

use Rutatiina\SalesOrder\Classes\Store as TxnStore;
use Rutatiina\SalesOrder\Classes\Approve as TxnApprove;
use Rutatiina\SalesOrder\Classes\Read as TxnRead;
use Rutatiina\SalesOrder\Classes\Copy as TxnCopy;
use Rutatiina\SalesOrder\Classes\Number as TxnNumber;
use Rutatiina\SalesOrder\Traits\Item as TxnItem;
use Rutatiina\SalesOrder\Classes\Edit as TxnEdit;
use Rutatiina\SalesOrder\Classes\Update as TxnUpdate;

class SalesOrderController extends Controller
{
    use ContactTrait;
    use ItemsSelect2DataTrait; //calls AccountingTrait
    use TxnItem; // >> get the item attributes template << !!important

    private  $txnEntreeSlug = 'order';

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

    private function nextNumber()
    {
        $txn = SalesOrder::latest()->first();
        $settings = Setting::first();

        return $settings->number_prefix.(str_pad((optional($txn)->number+1), $settings->minimum_number_length, "0", STR_PAD_LEFT)).$settings->number_postfix;
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new SalesOrder())->rgGetAttributes();

        $txnAttributes['number'] = $this->nextNumber();
        $txnAttributes['status'] = 'Approved';
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
        $TxnStore = new TxnStore();
        $TxnStore->txnEntreeSlug = $this->txnEntreeSlug;
        $TxnStore->txnInsertData = $request->all();
        $insert = $TxnStore->run();

        if ($insert == false) {
            return [
                'status'    => false,
                'messages'   => $TxnStore->errors
            ];
        }

        return [
            'status'    => true,
            'messages'   => ['Sales Order saved'],
            'number'    => 0,
            'callback'  => URL::route('sales-orders.show', [$insert->id], false)
        ];

    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        if (FacadesRequest::wantsJson()) {
            $TxnRead = new TxnRead();
            return $TxnRead->run($id);
        }
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $TxnEdit = new TxnEdit();
        $txnAttributes = $TxnEdit->run($id);

        $data = [
            'pageTitle' => 'Edit Sales order', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/sales-orders/'.$id, #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }

    public function update(Request $request)
    {
        $TxnStore = new TxnUpdate();
        $TxnStore->txnEntreeSlug = $this->txnEntreeSlug;
        $TxnStore->txnInsertData = $request->all();
        $insert = $TxnStore->run();

        if ($insert == false) {
            return [
                'status'    => false,
                'messages'  => $TxnStore->errors
            ];
        }

        return [
            'status'    => true,
            'messages'  => ['Sales Order updated'],
            'number'    => 0,
            'callback'  => URL::route('sales-orders.show', [$insert->id], false)
        ];
    }

    public function destroy($id)
	{
		$delete = SalesOrder::delete($id);

		if ($delete) {
			return [
				'status' => true,
				'message' => 'Sales Order deleted',
			];
		} else {
			return [
				'status' => false,
				'message' => ['Error: failed to deleted Sales order']
			];
		}
	}

	#-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $TxnApprove = new TxnApprove();
        $approve = $TxnApprove->run($id);

        if ($approve == false) {
            return [
                'status'    => false,
                'messages'   => $TxnApprove->errors
            ];
        }

        return [
            'status'    => true,
            'messages'   => ['Sales Order Approved'],
        ];

    }

    public function process()
	{}

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $TxnCopy = new TxnCopy();
        $txnAttributes = $TxnCopy->run($id);

        $TxnNumber = new TxnNumber();
        $txnAttributes['number'] = $TxnNumber->run($this->txnEntreeSlug);

        $data = [
            'pageTitle' => 'Copy Sales Order', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/financial-accounts/sales/sales-orders', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson()) {
            return $data;
        }
    }

    public function datatables(Request $request)
	{

        $txns = Transaction::setRoute('show', route('accounting.sales.sales-orders.show', '_id_'))
			->setRoute('edit', route('accounting.sales.sales-orders.edit', '_id_'))
			->setSortBy($request->sort_by)
			->paginate(false)
			->findByEntree($this->txnEntreeSlug);

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
