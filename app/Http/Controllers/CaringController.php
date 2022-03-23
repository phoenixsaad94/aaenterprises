<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Warehouse;
use App\Supplier;
use App\Product;
use App\Unit;
use App\Tax;
use App\Account;
use App\Sorting;
use App\ProductSorting;
use App\Caring;
use App\ProductCaring;
use App\Washing;
use App\ProductWashing;
use App\Product_Warehouse;
use App\Payment;
use App\PaymentWithCheque;
use App\PaymentWithCreditCard;
use App\PosSetting;
use DB;
use App\GeneralSetting;
use Stripe\Stripe;
use Auth;
use App\User;
use App\ProductVariant;
use App\ProductBatch;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CaringController extends Controller
{
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('purchases-index')) {
            if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            }
            else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
                $ending_date = date("Y-m-d");
            }
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            return view('caring.index', compact( 'lims_account_list', 'lims_warehouse_list', 'all_permission', 'lims_pos_setting_data', 'warehouse_id', 'starting_date', 'ending_date'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function caringData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
            5 => 'grand_total',
            6 => 'paid_amount',
        );

        $warehouse_id = $request->input('warehouse_id');
        if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $totalData = Caring::where('user_id', Auth::id())
                        ->whereDate('created_at', '>=' ,$request->input('starting_date'))
                        ->whereDate('created_at', '<=' ,$request->input('ending_date'))
                        ->count();
        elseif($warehouse_id != 0)
            $totalData = Caring::where('warehouse_id', $warehouse_id)->whereDate('created_at', '>=' ,$request->input('starting_date'))->whereDate('created_at', '<=' ,$request->input('ending_date'))->count();
        else
            $totalData = Caring::whereDate('created_at', '>=' ,$request->input('starting_date'))->whereDate('created_at', '<=' ,$request->input('ending_date'))->count();

        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value'))) {
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $carings = Caring::with('supplier', 'warehouse')->offset($start)
                            ->where('user_id', Auth::id())
                            ->whereDate('created_at', '>=' ,$request->input('starting_date'))
                            ->whereDate('created_at', '<=' ,$request->input('ending_date'))
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            elseif($warehouse_id != 0)
                $carings = Caring::with('supplier', 'warehouse')->offset($start)
                            ->where('warehouse_id', $warehouse_id)
                            ->whereDate('created_at', '>=' ,$request->input('starting_date'))
                            ->whereDate('created_at', '<=' ,$request->input('ending_date'))
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            else
                $carings = Caring::with('supplier', 'warehouse')->offset($start)
                            ->whereDate('created_at', '>=' ,$request->input('starting_date'))
                            ->whereDate('created_at', '<=' ,$request->input('ending_date'))
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
        }
        else
        {
            $search = $request->input('search.value');
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $carings =  Caring::select('carings.*')
                            ->with('supplier', 'warehouse')
                            ->leftJoin('suppliers', 'carings.supplier_id', '=', 'suppliers.id')
                            ->whereDate('carings.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                            ->where('carings.user_id', Auth::id())
                            ->orwhere([
                                ['carings.reference_no', 'LIKE', "%{$search}%"],
                                ['carings.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['suppliers.name', 'LIKE', "%{$search}%"],
                                ['carings.user_id', Auth::id()]
                            ])
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order,$dir)->get();

                $totalFiltered = Caring::
                            leftJoin('suppliers', 'carings.supplier_id', '=', 'suppliers.id')
                            ->whereDate('carings.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                            ->where('carings.user_id', Auth::id())
                            ->orwhere([
                                ['carings.reference_no', 'LIKE', "%{$search}%"],
                                ['carings.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['suppliers.name', 'LIKE', "%{$search}%"],
                                ['carings.user_id', Auth::id()]
                            ])
                            ->count();
            }
            else {
                $carings =  Caring::select('carings.*')
                            ->with('supplier', 'warehouse')
                            ->leftJoin('suppliers', 'carings.supplier_id', '=', 'suppliers.id')
                            ->whereDate('carings.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                            ->orwhere('carings.reference_no', 'LIKE', "%{$search}%")
                            ->orwhere('suppliers.name', 'LIKE', "%{$search}%")
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order,$dir)
                            ->get();

                $totalFiltered = Caring::
                                leftJoin('suppliers', 'carings.supplier_id', '=', 'suppliers.id')
                                ->whereDate('carings.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                                ->orwhere('carings.reference_no', 'LIKE', "%{$search}%")
                                ->orwhere('suppliers.name', 'LIKE', "%{$search}%")
                                ->count();
            }
        }
        $data = array();
        if(!empty($carings))
        {
            foreach ($carings as $key=>$caring)
            {
                $nestedData['id'] = $caring->id;
                $nestedData['key'] = $key;
                $nestedData['index'] = $key+1;
                $nestedData['date'] = date(config('date_format'), strtotime($caring->created_at->toDateString()));
                $nestedData['reference_no'] = $caring->reference_no;
                $nestedData['total_qty'] = $caring->total_qty;
                $nestedData['total_wastage'] = $caring->total_wastage;

                if($caring->supplier_id) {
                    $supplier = $caring->supplier;
                }
                else {
                    $supplier = new Supplier();
                }
                $nestedData['supplier'] = $supplier->name;
                if($caring->status == 1){
                    $nestedData['purchase_status'] = '<div class="badge badge-success">'.trans('file.Recieved').'</div>';
                    $purchase_status = trans('file.Recieved');
                }
                elseif($caring->status == 2){
                    $nestedData['purchase_status'] = '<div class="badge badge-success">'.trans('file.Partial').'</div>';
                    $purchase_status = trans('file.Partial');
                }
                elseif($caring->status == 3){
                    $nestedData['purchase_status'] = '<div class="badge badge-danger">'.trans('file.Pending').'</div>';
                    $purchase_status = trans('file.Pending');
                }
                else{
                    $nestedData['purchase_status'] = '<div class="badge badge-danger">'.trans('file.Ordered').'</div>';
                    $purchase_status = trans('file.Ordered');
                }

                if($caring->payment_status == 1)
                    $nestedData['payment_status'] = '<div class="badge badge-danger">'.trans('file.Due').'</div>';
                else
                    $nestedData['payment_status'] = '<div class="badge badge-success">'.trans('file.Paid').'</div>';

                $nestedData['grand_total'] = number_format($caring->grand_total, 2);
                $nestedData['paid_amount'] = number_format($caring->paid_amount, 2);
                $nestedData['due'] = number_format($caring->grand_total - $caring->paid_amount, 2);
                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> '.trans('file.View').'</button>
                                </li>';
                if(in_array("purchases-edit", $request['all_permission']))
                    $nestedData['options'] .= '<li>
                        <a href="'.route('carings.edit', $caring->id).'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.trans('file.edit').'</a>
                        </li>
                        <li>
                            <a href="'.route('carings.wash', $caring->id).'" class="btn btn-link"><i class="fa fa-plus"></i> '.trans('file.Add To Washing').'</a>
                        </li>';
                // $nestedData['options'] .=
                //     '<li>
                //         <button type="button" class="add-payment btn btn-link" data-id = "'.$caring->id.'" data-toggle="modal" data-target="#add-payment"><i class="fa fa-plus"></i> '.trans('file.Add Payment').'</button>
                //     </li>
                //     <li>
                //         <button type="button" class="get-payment btn btn-link" data-id = "'.$caring->id.'"><i class="fa fa-money"></i> '.trans('file.View Payment').'</button>
                //     </li>';
                if(in_array("purchases-delete", $request['all_permission']))
                    $nestedData['options'] .= \Form::open(["route" => ["carings.destroy", $caring->id], "method" => "DELETE"] ).'
                            <li>
                              <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.trans("file.delete").'</button>
                            </li>'.\Form::close().'
                        </ul>
                    </div>';

                // data for caring details by one click
                $user = User::find($caring->user_id);

                $nestedData['purchase'] = array( '[ "'.date(config('date_format'), strtotime($caring->created_at->toDateString())).'"', ' "'.$caring->reference_no.'"', ' "'.$purchase_status.'"',  ' "'.$caring->id.'"', ' "'.$caring->warehouse->name.'"', ' "'.$caring->warehouse->phone.'"', ' "'.$caring->warehouse->address.'"', ' "'.$supplier->name.'"', ' "'.$supplier->company_name.'"', ' "'.$supplier->email.'"', ' "'.$supplier->phone_number.'"', ' "'.$supplier->address.'"', ' "'.$supplier->city.'"', ' "'.$caring->total_tax.'"', ' "'.$caring->total_discount.'"', ' "'.$caring->total_cost.'"', ' "'.$caring->order_tax.'"', ' "'.$caring->order_tax_rate.'"', ' "'.$caring->order_discount.'"', ' "'.$caring->shipping_cost.'"', ' "'.$caring->grand_total.'"', ' "'.$caring->paid_amount.'"', ' "'.preg_replace('/\s+/S', " ", $caring->note).'"', ' "'.$user->name.'"', ' "'.$user->email.'"', ' "'.$caring->total_qty.'"', ' "'.$caring->total_wastage.'"]'
                );
                $get_product_caring_data = ProductCaring::where('caring_id', $caring->id)->select('product_id')->get();
                $get_product_data = [];
                $k=0;
                $product_info_string = "";
                foreach($get_product_caring_data as $get_product_caring_single){
                    if($k<2){
                        $get_name = Product::where('id', $get_product_caring_single->product_id)->select('name')->get();
                        array_push($get_product_data, $get_name[0]->name);
                    }
                    $k++;
                }
                foreach($get_product_data as $j => $get_product_single){
                    if($j!==0){
                        $product_info_string = $product_info_string.', ';
                    }
                    $product_info_string = $product_info_string.$get_product_data[$j];
                }
                $nestedData['product_info'] = Str::limit($product_info_string, 20, ' (...)');
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );

        echo json_encode($json_data);
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('purchases-add')){
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();

            return view('caring.create', compact('lims_supplier_list', 'lims_warehouse_list', 'lims_tax_list', 'lims_product_list_without_variant', 'lims_product_list_with_variant'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function productWithoutVariant()
    {
        return Product::ActiveStandard()->select('id', 'name', 'code')
                ->whereNull('is_variant')->get();
    }

    public function productWithVariant()
    {
        return Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->ActiveStandard()
                ->whereNotNull('is_variant')
                ->select('products.id', 'products.name', 'product_variants.item_code')
                ->orderBy('position')->get();
    }

    public function limsProductSearch(Request $request)
    {
        $product_code = explode("(", $request['data']);
        $product_code[0] = rtrim($product_code[0], " ");
        $lims_product_data = Product::where([
            ['code', $product_code[0]],
            ['is_active', true]
        ])->first();
        if(!$lims_product_data) {
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.*', 'product_variants.item_code')
                ->where([
                    ['product_variants.item_code', $product_code[0]],
                    ['products.is_active', true]
                ])->first();
        }

        $product[] = $lims_product_data->name;
        if($lims_product_data->is_variant)
            $product[] = $lims_product_data->item_code;
        else
            $product[] = $lims_product_data->code;
        $product[] = $lims_product_data->cost;

        if ($lims_product_data->tax_id) {
            $lims_tax_data = Tax::find($lims_product_data->tax_id);
            $product[] = $lims_tax_data->rate;
            $product[] = $lims_tax_data->name;
        } else {
            $product[] = 0;
            $product[] = 'No Tax';
        }
        $product[] = $lims_product_data->tax_method;

        // $units = Unit::where("base_unit", $lims_product_data->unit_id)
        //             ->orWhere('id', $lims_product_data->unit_id)
        //             ->get();
        $units = Unit::get();
        $unit_name = array();
        $unit_operator = array();
        $unit_operation_value = array();
        foreach ($units as $unit) {
            // if ($lims_product_data->caring_unit_id == $unit->id) {
            //     array_unshift($unit_name, $unit->unit_name);
            //     array_unshift($unit_operator, $unit->operator);
            //     array_unshift($unit_operation_value, $unit->operation_value);
            // } else {
                $unit_name[]  = $unit->unit_name;
                $unit_operator[] = $unit->operator;
                $unit_operation_value[] = $unit->operation_value;
            // }
        }

        $product[] = implode(",", $unit_name) . ',';
        $product[] = implode(",", $unit_operator) . ',';
        $product[] = implode(",", $unit_operation_value) . ',';
        $product[] = $lims_product_data->id;
        $product[] = $lims_product_data->is_batch;
        return $product;
    }

    public function store(Request $request)
    {
        $data = $request->except('document');
        // return dd($data);
        $data['user_id'] = Auth::id();
        $data['reference_no'] = 'pr-' . date("Ymd") . '-'. date("his");
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $documentName = $document->getClientOriginalName();
            $document->move('public/documents/caring', $documentName);
            $data['document'] = $documentName;
        }
        //return dd($data);
        Caring::create($data);

        $lims_caring_data = Caring::latest()->first();
        $product_id = $data['product_id'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $recieved = $data['qty'];
        $wastage = $data['wastage'];
        // $recieved = $data['recieved'];
        // $batch_no = $data['batch_no'];
        // $expired_date = $data['expired_date'];
        $caring_unit = $data['caring_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $caring_unit_2 = $data['caring_unit_2'];
        $caring_unit_value_2 = $data['caring_unit_value_2'];
        $caring_unit_3 = $data['caring_unit_3'];
        $caring_unit_value_3 = $data['caring_unit_value_3'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_caring = [];


        foreach ($product_id as $i => $id) {
            $lims_caring_unit_data  = Unit::where('unit_name', $caring_unit[$i])->first();
            $lims_caring_unit_data_2  = Unit::where('unit_name', $caring_unit_2[$i])->first();
            $lims_caring_unit_value_2 = $caring_unit_value_2[$i];
            $lims_caring_unit_data_3  = Unit::where('unit_name', $caring_unit_3[$i])->first();
            $lims_caring_unit_value_3 = $caring_unit_value_3[$i];


            if ($lims_caring_unit_data->operator == '*') {
                $quantity = $recieved[$i] * $lims_caring_unit_data->operation_value;
            } else {
                $quantity = $recieved[$i] / $lims_caring_unit_data->operation_value;
            }
            $lims_product_data = Product::find($id);

            //dealing with product barch
            // if($batch_no[$i]) {
                //     $product_batch_data = ProductBatch::where([
            //                             ['product_id', $lims_product_data->id],
            //                             ['batch_no', $batch_no[$i]]
            //                         ])->first();
            //     if($product_batch_data) {
            //         $product_batch_data->expired_date = $expired_date[$i];
            //         $product_batch_data->qty += $quantity;
            //         $product_batch_data->save();
            //     }
            //     else {
                //         $product_batch_data = ProductBatch::create([
            //                                 'product_id' => $lims_product_data->id,
            //                                 'batch_no' => $batch_no[$i],
            //                                 'expired_date' => $expired_date[$i],
            //                                 'qty' => $quantity
            //                             ]);
            //     }
            //     $product_caring['product_batch_id'] = $product_batch_data->id;
            // }
            // else
            $product_caring['product_batch_id'] = null;

            // if($lims_product_data->is_variant) {
            //     $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($lims_product_data->id, $product_code[$i])->first();
            //     $lims_product_warehouse_data = Product_Warehouse::where([
                //         ['product_id', $id],
            //         ['variant_id', $lims_product_variant_data->variant_id],
            //         ['warehouse_id', $data['warehouse_id']]
            //     ])->first();
            //     $product_caring['variant_id'] = $lims_product_variant_data->variant_id;
            //     //add quantity to product variant table
            //     $lims_product_variant_data->qty += $quantity;
            //     $lims_product_variant_data->save();
            // }
            // else {
                $product_caring['variant_id'] = null;
                if($product_caring['product_batch_id']) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['product_batch_id', $product_caring['product_batch_id'] ],
                        ['warehouse_id', $data['warehouse_id'] ],
                    ])->first();
                }
                else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['warehouse_id', $data['warehouse_id'] ],
                    ])->first();
                }
                // }
            //add quantity to product table
            // $lims_product_data->qty = $lims_product_data->qty + $quantity;
            $lims_product_data->save();
            //add quantity to warehouse
            if ($lims_product_warehouse_data) {
                // $lims_product_warehouse_data->qty = $lims_product_warehouse_data->qty + $quantity;
            }
            else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $id;
                $lims_product_warehouse_data->product_batch_id = $product_caring['product_batch_id'];
                $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                $lims_product_warehouse_data->qty = $quantity;
                if($lims_product_data->is_variant)
                $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
            }

            $lims_product_warehouse_data->save();

            $product_caring['caring_id'] = $lims_caring_data->id ;
            $product_caring['sorting_id'] = $lims_caring_data->sorting_id ;
            $product_caring['purchase_id'] = $lims_caring_data->purchase_id ;
            $product_caring['product_id'] = $id;
            $product_caring['qty'] = $qty[$i];
            $product_caring['recieved'] = $recieved[$i];
            $product_caring['wastage'] = $wastage[$i];
            $product_caring['caring_unit_id'] = $lims_caring_unit_data->id;
            $product_caring['net_unit_cost'] = $net_unit_cost[$i];
            $product_caring['caring_unit_id_2'] = $lims_caring_unit_data_2->id;
            $product_caring['caring_unit_value_2'] = $lims_caring_unit_value_2;
            $product_caring['caring_unit_id_3'] = $lims_caring_unit_data_3->id;
            $product_caring['caring_unit_value_3'] = $lims_caring_unit_value_3;
            $product_caring['discount'] = $discount[$i];
            $product_caring['tax_rate'] = $tax_rate[$i];
            $product_caring['tax'] = $tax[$i];
            $product_caring['total'] = $total[$i];

            ProductCaring::create($product_caring);
        }

        return redirect('carings')->with('message', 'Caring created successfully');
    }

    public function productCaringData($id)
    {
        $lims_product_caring_data = ProductCaring::where('caring_id', $id)->get();
        foreach ($lims_product_caring_data as $key => $product_caring_data) {
            $product = Product::find($product_caring_data->product_id);
            $unit = Unit::find($product_caring_data->purchase_unit_id);
            if($product_caring_data->variant_id) {
                $lims_product_variant_data = ProductVariant::FindExactProduct($product->id, $product_caring_data->variant_id)->select('item_code')->first();
                $product->code = $lims_product_variant_data->item_code;
            }
            if($product_caring_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no')->find($product_caring_data->product_batch_id);
                $product_caring[7][$key] = $product_batch_data->batch_no;
            }
            else
                $product_caring[7][$key] = 'N/A';
            $product_caring[0][$key] = $product->name . ' [' . $product->code.']';
            $product_caring[1][$key] = $product_caring_data->qty;
            $product_caring[2][$key] = $unit->unit_code;
            $product_caring[3][$key] = $product_caring_data->tax;
            $product_caring[4][$key] = $product_caring_data->tax_rate;
            $product_caring[5][$key] = $product_caring_data->discount;
            $product_caring[6][$key] = $product_caring_data->total;
            $product_caring[8][$key] = $product_caring_data->wastage;
        }
        return $product_caring;
    }

    public function caringByCsv()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('purchases-add')){
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();

            return view('caring.import', compact('lims_supplier_list', 'lims_warehouse_list', 'lims_tax_list'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function importCaring(Request $request)
    {
        //get the file
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        //checking if this is a CSV file
        if($ext != 'csv')
            return redirect()->back()->with('message', 'Please upload a CSV file');

        $filePath=$upload->getRealPath();
        $file_handle = fopen($filePath, 'r');
        $i = 0;
        //validate the file
        while (!feof($file_handle) ) {
            $current_line = fgetcsv($file_handle);
            if($current_line && $i > 0){
                $product_data[] = Product::where('code', $current_line[0])->first();
                if(!$product_data[$i-1])
                    return redirect()->back()->with('message', 'Product with this code '.$current_line[0].' does not exist!');
                $unit[] = Unit::where('unit_code', $current_line[2])->first();
                if(!$unit[$i-1])
                    return redirect()->back()->with('message', 'Caring unit does not exist!');
                if(strtolower($current_line[5]) != "no tax"){
                    $tax[] = Tax::where('name', $current_line[5])->first();
                    if(!$tax[$i-1])
                        return redirect()->back()->with('message', 'Tax name does not exist!');
                }
                else
                    $tax[$i-1]['rate'] = 0;

                $qty[] = $current_line[1];
                $cost[] = $current_line[3];
                $discount[] = $current_line[4];
            }
            $i++;
        }

        $data = $request->except('file');
        $data['reference_no'] = 'pr-' . date("Ymd") . '-'. date("his");
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = $data['reference_no'] . '.' . $ext;
            $document->move('public/documents/caring', $documentName);
            $data['document'] = $documentName;
        }
        $item = 0;
        $grand_total = $data['shipping_cost'];
        $data['user_id'] = Auth::id();
        Caring::create($data);
        $lims_caring_data = Caring::latest()->first();

        foreach ($product_data as $key => $product) {
            if($product['tax_method'] == 1){
                $net_unit_cost = $cost[$key] - $discount[$key];
                $product_tax = $net_unit_cost * ($tax[$key]['rate'] / 100) * $qty[$key];
                $total = ($net_unit_cost * $qty[$key]) + $product_tax;
            }
            elseif($product['tax_method'] == 2){
                $net_unit_cost = (100 / (100 + $tax[$key]['rate'])) * ($cost[$key] - $discount[$key]);
                $product_tax = ($cost[$key] - $discount[$key] - $net_unit_cost) * $qty[$key];
                $total = ($cost[$key] - $discount[$key]) * $qty[$key];
            }
            if($data['status'] == 1){
                if($unit[$key]['operator'] == '*')
                    $quantity = $qty[$key] * $unit[$key]['operation_value'];
                elseif($unit[$key]['operator'] == '/')
                    $quantity = $qty[$key] / $unit[$key]['operation_value'];
                $product['qty'] += $quantity;
                $product_warehouse = Product_Warehouse::where([
                    ['product_id', $product['id']],
                    ['warehouse_id', $data['warehouse_id']]
                ])->first();
                if($product_warehouse) {
                    $product_warehouse->qty += $quantity;
                    $product_warehouse->save();
                }
                else {
                    $lims_product_warehouse_data = new Product_Warehouse();
                    $lims_product_warehouse_data->product_id = $product['id'];
                    $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                    $lims_product_warehouse_data->qty = $quantity;
                    $lims_product_warehouse_data->save();
                }
                $product->save();
            }

            $product_caring = new ProductCaring();
            $product_caring->caring_id = $lims_caring_data->id;
            $product_caring->product_id = $product['id'];
            $product_caring->qty = $qty[$key];
            if($data['status'] == 1)
                $product_caring->recieved = $qty[$key];
            else
                $product_caring->recieved = 0;
            $product_caring->caring_unit_id = $unit[$key]['id'];
            $product_caring->net_unit_cost = number_format((float)$net_unit_cost, 2, '.', '');
            $product_caring->discount = $discount[$key] * $qty[$key];
            $product_caring->tax_rate = $tax[$key]['rate'];
            $product_caring->tax = number_format((float)$product_tax, 2, '.', '');
            $product_caring->total = number_format((float)$total, 2, '.', '');
            $product_caring->save();
            $lims_caring_data->total_qty += $qty[$key];
            $lims_caring_data->total_discount += $discount[$key] * $qty[$key];
            $lims_caring_data->total_tax += number_format((float)$product_tax, 2, '.', '');
            $lims_caring_data->total_cost += number_format((float)$total, 2, '.', '');
        }
        $lims_caring_data->item = $key + 1;
        $lims_caring_data->order_tax = ($lims_caring_data->total_cost - $lims_caring_data->order_discount) * ($data['order_tax_rate'] / 100);
        $lims_caring_data->grand_total = ($lims_caring_data->total_cost + $lims_caring_data->order_tax + $lims_caring_data->shipping_cost) - $lims_caring_data->order_discount;
        $lims_caring_data->save();
        return redirect('carings');
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('purchases-edit')){
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_purchase_data = Caring::find($id);
            $lims_product_purchase_data = ProductCaring::where('caring_id', $id)->get();

            return view('caring.edit', compact('lims_warehouse_list', 'lims_supplier_list', 'lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_tax_list', 'lims_purchase_data', 'lims_product_purchase_data'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');

    }

    public function addPrice($id){
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('purchases-edit')){
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_caring_data = Caring::find($id);
            $lims_product_caring_data = ProductCaring::where('caring_id', $id)->get();

            return view('caring.edit', compact('lims_warehouse_list', 'lims_supplier_list', 'lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_tax_list', 'lims_caring_data', 'lims_product_caring_data'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');

    }

    public function addToWash($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('purchases-edit')){
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_caring_data = Caring::find($id)->toArray();
            $lims_product_caring_data = ProductCaring::where('caring_id', $id)->get()->toArray();

            // unset($lims_purchase_data['id']);
            // unset($lims_purchase_data['created_at']);
            // unset($lims_purchase_data['updated_at']);
            $lims_caring_data['caring_id'] = $id;
            $lims_caring_data['total_wastage'] = 0;
            $lims_caring_data['note'] = '';

            $washing_create = Washing::create($lims_caring_data);
            foreach($lims_product_caring_data as $caring_data){
                // unset($caring_data['id']);
                // unset($caring_data['created_at']);
                // unset($caring_data['updated_at']);
                $caring_data['caring_id'] = $id;
                $caring_data['washing_id'] = $washing_create->id;
                $caring_data['wastage'] = 0;
                $purchase_washing_create = ProductWashing::create($caring_data);
                // dd($caring_data['caring_id'], $caring_data, $purchase_caring_create);
                // ProductCaring::where('caring_id', $id)->delete();
            }
            // Caring::where('id', $id)->delete();
            // dd($lims_purchase_data, $lims_product_purchase_data, $caring_data);
            return redirect('washings')->with('message', 'Import successfully added to washing');
            // return redirect()->back()->with('success', 'Import successfully added to caring');
            // return view('purchase.edit', compact('lims_warehouse_list', 'lims_supplier_list', 'lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_tax_list', 'lims_purchase_data', 'lims_product_purchase_data'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');

    }

    public function update(Request $request, $id)
    {
        $data = $request->except('document');
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $documentName = $document->getClientOriginalName();
            $document->move('public/caring/documents', $documentName);
            $data['document'] = $documentName;
        }
        // return dd($data);
        $balance = $data['grand_total'] - $data['paid_amount'];
        if ($balance < 0 || $balance > 0) {
            $data['payment_status'] = 1;
        } else {
            $data['payment_status'] = 2;
        }
        $lims_caring_data = Caring::find($id);

        $lims_product_caring_data = ProductCaring::where('caring_id', $id)->get();

        $product_id = $data['product_id'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $recieved = $data['qty'];
        $wastage = $data['wastage'];
        // $recieved = $data['recieved'];
        // $batch_no = $data['batch_no'];
        // $expired_date = $data['expired_date'];
        $caring_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $caring_unit_2 = $data['purchase_unit_2'];
        $caring_unit_value_2 = $data['purchase_unit_value_2'];
        $caring_unit_3 = $data['purchase_unit_3'];
        $caring_unit_value_3 = $data['purchase_unit_value_3'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_caring = [];
        $lims_product_purchase_data = [];
        $lims_product_sorting_data = [];

        foreach ($lims_product_caring_data as $product_caring_data) {

            $old_recieved_value = $product_caring_data->recieved;
            $lims_caring_unit_data = Unit::find($product_caring_data->purchase_unit_id);

            if ($lims_caring_unit_data->operator == '*') {
                $old_recieved_value = $old_recieved_value * $lims_caring_unit_data->operation_value;
            } else {
                $old_recieved_value = $old_recieved_value / $lims_caring_unit_data->operation_value;
            }
            $lims_product_data = Product::find($product_caring_data->product_id);
            // if($lims_product_data->is_variant) {
            //     $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProduct($lims_product_data->id, $product_caring_data->variant_id)->first();
            //     $lims_product_warehouse_data = Product_Warehouse::where([
            //         ['product_id', $lims_product_data->id],
            //         ['variant_id', $product_caring_data->variant_id],
            //         ['warehouse_id', $lims_caring_data->warehouse_id]
            //     ])->first();
            //     $lims_product_variant_data->qty -= $old_recieved_value;
            //     $lims_product_variant_data->save();
            // }
            // elseif($product_caring_data->product_batch_id) {
            //     $product_batch_data = ProductBatch::find($product_caring_data->product_batch_id);
            //     $product_batch_data->qty -= $old_recieved_value;
            //     $product_batch_data->save();

            //     $lims_product_warehouse_data = Product_Warehouse::where([
            //         ['product_id', $product_caring_data->product_id],
            //         ['product_batch_id', $product_caring_data->product_batch_id],
            //         ['warehouse_id', $lims_caring_data->warehouse_id],
            //     ])->first();
            // }
            // else {
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $product_caring_data->product_id],
                    ['warehouse_id', $lims_caring_data->warehouse_id],
                ])->first();
            // }


            array_push($lims_product_purchase_data, $product_caring_data->purchase_id);
            array_push($lims_product_sorting_data, $product_caring_data->sorting_id);

            // $lims_product_data->qty -= $old_recieved_value;
            // $lims_product_warehouse_data->qty -= $old_recieved_value;
            $lims_product_warehouse_data->save();
            $lims_product_data->save();
            $product_caring_data->delete();
        }

        foreach ($product_id as $key => $pro_id) {

            $lims_caring_unit_data = Unit::where('unit_name', $caring_unit[$key])->first();
            $lims_caring_unit_data_2  = Unit::where('unit_name', $caring_unit_2[$key])->first();
            $lims_caring_unit_value_2 = $caring_unit_value_2[$key];
            $lims_caring_unit_data_3  = Unit::where('unit_name', $caring_unit_3[$key])->first();
            $lims_caring_unit_value_3 = $caring_unit_value_3[$key];

            if ($lims_caring_unit_data->operator == '*') {
                $new_recieved_value = $recieved[$key] * $lims_caring_unit_data->operation_value;
            } else {
                $new_recieved_value = $recieved[$key] / $lims_caring_unit_data->operation_value;
            }

            $lims_product_data = Product::find($pro_id);
            //dealing with product batch
            // if($batch_no[$key]) {
            //     $product_batch_data = ProductBatch::where([
            //                             ['product_id', $lims_product_data->id],
            //                             ['batch_no', $batch_no[$key]]
            //                         ])->first();
            //     if($product_batch_data) {
            //         $product_batch_data->qty += $new_recieved_value;
            //         $product_batch_data->expired_date = $expired_date[$key];
            //         $product_batch_data->save();
            //     }
            //     else {
            //         $product_batch_data = ProductBatch::create([
            //                                 'product_id' => $lims_product_data->id,
            //                                 'batch_no' => $batch_no[$key],
            //                                 'expired_date' => $expired_date[$key],
            //                                 'qty' => $new_recieved_value
            //                             ]);
            //     }
            //     $product_caring['product_batch_id'] = $product_batch_data->id;
            // }
            // else
                $product_caring['product_batch_id'] = null;

            // if($lims_product_data->is_variant) {
            //     $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
            //     $lims_product_warehouse_data = Product_Warehouse::where([
            //         ['product_id', $pro_id],
            //         ['variant_id', $lims_product_variant_data->variant_id],
            //         ['warehouse_id', $data['warehouse_id']]
            //     ])->first();
            //     $product_caring['variant_id'] = $lims_product_variant_data->variant_id;
            //     //add quantity to product variant table
            //     $lims_product_variant_data->qty += $new_recieved_value;
            //     $lims_product_variant_data->save();
            // }
            // else {
                $product_caring['variant_id'] = null;
                if($product_caring['product_batch_id']) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['product_batch_id', $product_caring['product_batch_id'] ],
                        ['warehouse_id', $data['warehouse_id'] ],
                    ])->first();
                }
                else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['warehouse_id', $data['warehouse_id'] ],
                    ])->first();
                }
            // }

            // $lims_product_data->qty += $new_recieved_value;
            if($lims_product_warehouse_data){
                // $lims_product_warehouse_data->qty += $new_recieved_value;
                $lims_product_warehouse_data->save();
            }
            else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $pro_id;
                $lims_product_warehouse_data->product_batch_id = $product_caring['product_batch_id'];
                if($lims_product_data->is_variant)
                    $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
                $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                $lims_product_warehouse_data->qty = $new_recieved_value;
                $lims_product_warehouse_data->save();
            }

            $lims_product_data->save();

            $product_caring['purchase_id'] = $lims_product_purchase_data[0];
            $product_caring['sorting_id'] = $lims_product_sorting_data[0];
            $product_caring['caring_id'] = $id;
            $product_caring['product_id'] = $pro_id;
            $product_caring['qty'] = $qty[$key];
            $product_caring['recieved'] = $recieved[$key];
            $product_caring['wastage'] = $wastage[$key];
            $product_caring['purchase_unit_id'] = $lims_caring_unit_data->id;
            $product_caring['net_unit_cost'] = $net_unit_cost[$key];
            $product_caring['purchase_unit_id_2'] = $lims_caring_unit_data_2->id;
            $product_caring['purchase_unit_value_2'] = $lims_caring_unit_value_2;
            $product_caring['purchase_unit_id_3'] = $lims_caring_unit_data_3->id;
            $product_caring['purchase_unit_value_3'] = $lims_caring_unit_value_3;
            $product_caring['discount'] = $discount[$key];
            $product_caring['tax_rate'] = $tax_rate[$key];
            $product_caring['tax'] = $tax[$key];
            $product_caring['total'] = $total[$key];
            ProductCaring::create($product_caring);
        }

        $lims_caring_data->update($data);
        return redirect('carings')->with('message', 'Caring updated successfully');
    }

    public function addPayment(Request $request)
    {
        $data = $request->all();
        $lims_caring_data = Caring::find($data['caring_id']);
        $lims_caring_data->paid_amount += $data['amount'];
        $balance = $lims_caring_data->grand_total - $lims_caring_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_caring_data->payment_status = 1;
        elseif ($balance == 0)
            $lims_caring_data->payment_status = 2;
        $lims_caring_data->save();

        if($data['paid_by_id'] == 1)
            $paying_method = 'Cash';
        elseif ($data['paid_by_id'] == 2)
            $paying_method = 'Gift Card';
        elseif ($data['paid_by_id'] == 3)
            $paying_method = 'Credit Card';
        else
            $paying_method = 'Cheque';

        $lims_payment_data = new Payment();
        $lims_payment_data->user_id = Auth::id();
        $lims_payment_data->caring_id = $lims_caring_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->payment_reference = 'ppr-' . date("Ymd") . '-'. date("his");
        $lims_payment_data->amount = $data['amount'];
        $lims_payment_data->change = $data['paying_amount'] - $data['amount'];
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $lims_payment_data->save();

        $lims_payment_data = Payment::latest()->first();
        $data['payment_id'] = $lims_payment_data->id;

        if($paying_method == 'Credit Card'){
            $lims_pos_setting_data = PosSetting::latest()->first();
            Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            $token = $data['stripeToken'];
            $amount = $data['amount'];

            // Charge the Customer
            $charge = \Stripe\Charge::create([
                'amount' => $amount * 100,
                'currency' => 'usd',
                'source' => $token,
            ]);

            $data['charge_id'] = $charge->id;
            PaymentWithCreditCard::create($data);
        }
        elseif ($paying_method == 'Cheque') {
            PaymentWithCheque::create($data);
        }
        return redirect('carings')->with('message', 'Payment created successfully');
    }

    public function getPayment($id)
    {
        $lims_payment_list = Payment::where('caring_id', $id)->get();
        $date = [];
        $payment_reference = [];
        $paid_amount = [];
        $paying_method = [];
        $payment_id = [];
        $payment_note = [];
        $cheque_no = [];
        $change = [];
        $paying_amount = [];
        $account_name = [];
        $account_id = [];
        foreach ($lims_payment_list as $payment) {
            $date[] = date(config('date_format'), strtotime($payment->created_at->toDateString())) . ' '. $payment->created_at->toTimeString();
            $payment_reference[] = $payment->payment_reference;
            $paid_amount[] = $payment->amount;
            $change[] = $payment->change;
            $paying_method[] = $payment->paying_method;
            $paying_amount[] = $payment->amount + $payment->change;
            if($payment->paying_method == 'Cheque'){
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id',$payment->id)->first();
                $cheque_no[] = $lims_payment_cheque_data->cheque_no;
            }
            else{
                $cheque_no[] = null;
            }
            $payment_id[] = $payment->id;
            $payment_note[] = $payment->payment_note;
            $lims_account_data = Account::find($payment->account_id);
            $account_name[] = $lims_account_data->name;
            $account_id[] = $lims_account_data->id;
        }
        $payments[] = $date;
        $payments[] = $payment_reference;
        $payments[] = $paid_amount;
        $payments[] = $paying_method;
        $payments[] = $payment_id;
        $payments[] = $payment_note;
        $payments[] = $cheque_no;
        $payments[] = $change;
        $payments[] = $paying_amount;
        $payments[] = $account_name;
        $payments[] = $account_id;

        return $payments;
    }

    public function updatePayment(Request $request)
    {
        $data = $request->all();
        $lims_payment_data = Payment::find($data['payment_id']);
        $lims_caring_data = Caring::find($lims_payment_data->caring_id);
        //updating caring table
        $amount_dif = $lims_payment_data->amount - $data['edit_amount'];
        $lims_caring_data->paid_amount = $lims_caring_data->paid_amount - $amount_dif;
        $balance = $lims_caring_data->grand_total - $lims_caring_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_caring_data->payment_status = 1;
        elseif ($balance == 0)
            $lims_caring_data->payment_status = 2;
        $lims_caring_data->save();

        //updating payment data
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->amount = $data['edit_amount'];
        $lims_payment_data->change = $data['edit_paying_amount'] - $data['edit_amount'];
        $lims_payment_data->payment_note = $data['edit_payment_note'];
        if($data['edit_paid_by_id'] == 1)
            $lims_payment_data->paying_method = 'Cash';
        elseif ($data['edit_paid_by_id'] == 2)
            $lims_payment_data->paying_method = 'Gift Card';
        elseif ($data['edit_paid_by_id'] == 3){
            $lims_pos_setting_data = PosSetting::latest()->first();
            \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            $token = $data['stripeToken'];
            $amount = $data['edit_amount'];
            if($lims_payment_data->paying_method == 'Credit Card'){
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $lims_payment_data->id)->first();

                \Stripe\Refund::create(array(
                  "charge" => $lims_payment_with_credit_card_data->charge_id,
                ));

                $charge = \Stripe\Charge::create([
                    'amount' => $amount * 100,
                    'currency' => 'usd',
                    'source' => $token,
                ]);

                $lims_payment_with_credit_card_data->charge_id = $charge->id;
                $lims_payment_with_credit_card_data->save();
            }
            else{
                // Charge the Customer
                $charge = \Stripe\Charge::create([
                    'amount' => $amount * 100,
                    'currency' => 'usd',
                    'source' => $token,
                ]);

                $data['charge_id'] = $charge->id;
                PaymentWithCreditCard::create($data);
            }
            $lims_payment_data->paying_method = 'Credit Card';
        }
        else{
            if($lims_payment_data->paying_method == 'Cheque'){
                $lims_payment_data->paying_method = 'Cheque';
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $data['payment_id'])->first();
                $lims_payment_cheque_data->cheque_no = $data['edit_cheque_no'];
                $lims_payment_cheque_data->save();
            }
            else{
                $lims_payment_data->paying_method = 'Cheque';
                $data['cheque_no'] = $data['edit_cheque_no'];
                PaymentWithCheque::create($data);
            }
        }
        $lims_payment_data->save();
        return redirect('carings')->with('message', 'Payment updated successfully');
    }

    public function deletePayment(Request $request)
    {
        $lims_payment_data = Payment::find($request['id']);
        $lims_caring_data = Caring::where('id', $lims_payment_data->caring_id)->first();
        $lims_caring_data->paid_amount -= $lims_payment_data->amount;
        $balance = $lims_caring_data->grand_total - $lims_caring_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_caring_data->payment_status = 1;
        elseif ($balance == 0)
            $lims_caring_data->payment_status = 2;
        $lims_caring_data->save();

        if($lims_payment_data->paying_method == 'Credit Card'){
            $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $request['id'])->first();
            $lims_pos_setting_data = PosSetting::latest()->first();
            \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            \Stripe\Refund::create(array(
              "charge" => $lims_payment_with_credit_card_data->charge_id,
            ));

            $lims_payment_with_credit_card_data->delete();
        }
        elseif ($lims_payment_data->paying_method == 'Cheque') {
            $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $request['id'])->first();
            $lims_payment_cheque_data->delete();
        }
        $lims_payment_data->delete();
        return redirect('carings')->with('not_permitted', 'Payment deleted successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $caring_id = $request['caringIdArray'];
        foreach ($caring_id as $id) {
            $lims_caring_data = Caring::find($id);
            $lims_product_caring_data = ProductCaring::where('caring_id', $id)->get();
            $lims_payment_data = Payment::where('caring_id', $id)->get();
            foreach ($lims_product_caring_data as $product_caring_data) {
                $lims_caring_unit_data = Unit::find($product_caring_data->caring_unit_id);
                if ($lims_caring_unit_data->operator == '*')
                    $recieved_qty = $product_caring_data->recieved * $lims_caring_unit_data->operation_value;
                else
                    $recieved_qty = $product_caring_data->recieved / $lims_caring_unit_data->operation_value;

                $lims_product_data = Product::find($product_caring_data->product_id);
                if($product_caring_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_caring_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_caring_data->product_id, $product_caring_data->variant_id, $lims_caring_data->warehouse_id)
                        ->first();
                    // $lims_product_variant_data->qty -= $recieved_qty;
                    $lims_product_variant_data->save();
                }
                elseif($product_caring_data->product_batch_id) {
                    $lims_product_batch_data = ProductBatch::find($product_caring_data->product_batch_id);
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_caring_data->product_batch_id],
                        ['warehouse_id', $lims_caring_data->warehouse_id]
                    ])->first();

                    // $lims_product_batch_data->qty -= $recieved_qty;
                    $lims_product_batch_data->save();
                }
                else {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_caring_data->product_id, $lims_caring_data->warehouse_id)
                        ->first();
                }

                // $lims_product_data->qty -= $recieved_qty;
                // $lims_product_warehouse_data->qty -= $recieved_qty;

                $lims_product_warehouse_data->save();
                $lims_product_data->save();
                $product_caring_data->delete();
            }
            foreach ($lims_payment_data as $payment_data) {
                if($payment_data->paying_method == "Cheque"){
                    $payment_with_cheque_data = PaymentWithCheque::where('payment_id', $payment_data->id)->first();
                    $payment_with_cheque_data->delete();
                }
                elseif($payment_data->paying_method == "Credit Card"){
                    $payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment_data->id)->first();
                    $lims_pos_setting_data = PosSetting::latest()->first();
                    \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                    \Stripe\Refund::create(array(
                      "charge" => $payment_with_credit_card_data->charge_id,
                    ));

                    $payment_with_credit_card_data->delete();
                }
                $payment_data->delete();
            }

            if($lims_caring_data){
                $lims_caring_data->delete();
            }
        }
        return 'Caring deleted successfully!';
    }

    public function destroy($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('purchases-delete')){
            $lims_caring_data = Caring::find($id);
            $lims_product_caring_data = ProductCaring::where('purchase_id', $id)->get();
            // $lims_payment_data = Payment::where('caring_id', $id)->get();
            foreach ($lims_product_caring_data as $product_caring_data) {
                $lims_caring_unit_data = Unit::find($product_caring_data->caring_unit_id);
                if ($lims_caring_unit_data->operator == '*')
                    $recieved_qty = $product_caring_data->recieved * $lims_caring_unit_data->operation_value;
                else
                    $recieved_qty = $product_caring_data->recieved / $lims_caring_unit_data->operation_value;

                $lims_product_data = Product::find($product_caring_data->product_id);
                if($product_caring_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_caring_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_caring_data->product_id, $product_caring_data->variant_id, $lims_caring_data->warehouse_id)
                        ->first();
                    // $lims_product_variant_data->qty -= $recieved_qty;
                    $lims_product_variant_data->save();
                }
                elseif($product_caring_data->product_batch_id) {
                    $lims_product_batch_data = ProductBatch::find($product_caring_data->product_batch_id);
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_caring_data->product_batch_id],
                        ['warehouse_id', $lims_caring_data->warehouse_id]
                    ])->first();

                    // $lims_product_batch_data->qty -= $recieved_qty;
                    $lims_product_batch_data->save();
                }
                else {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_caring_data->product_id, $lims_caring_data->warehouse_id)
                        ->first();
                }

                // $lims_product_data->qty -= $recieved_qty;
                // $lims_product_warehouse_data->qty -= $recieved_qty;

                $lims_product_warehouse_data->save();
                $lims_product_data->save();
                $product_caring_data->delete();
            }
            // foreach ($lims_payment_data as $payment_data) {
            //     if($payment_data->paying_method == "Cheque"){
            //         $payment_with_cheque_data = PaymentWithCheque::where('payment_id', $payment_data->id)->first();
            //         $payment_with_cheque_data->delete();
            //     }
            //     elseif($payment_data->paying_method == "Credit Card"){
            //         $payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment_data->id)->first();
            //         $lims_pos_setting_data = PosSetting::latest()->first();
            //         \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            //         \Stripe\Refund::create(array(
            //           "charge" => $payment_with_credit_card_data->charge_id,
            //         ));

            //         $payment_with_credit_card_data->delete();
            //     }
            //     $payment_data->delete();
            // }

            if($lims_caring_data){
                $lims_caring_data->delete();
            }

            return redirect('carings')->with('not_permitted', 'Caring deleted successfully');
        }

    }
}
