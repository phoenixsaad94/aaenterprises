@extends('layout.main') @section('content')
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.Update Packing')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => ['packings.update', $lims_purchase_data->id], 'method' => 'put', 'files' => true, 'id' => 'purchase-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Reference No')}}</label>
                                            <p><strong>{{ $lims_purchase_data->reference_no }}</strong> </p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Warehouse')}} *</label>
                                            <input type="hidden" name="warehouse_id_hidden" value="{{$lims_purchase_data->warehouse_id}}" />
                                            <select required name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Supplier')}}</label>
                                            <input type="hidden" name="supplier_id_hidden" value="{{ $lims_purchase_data->supplier_id }}" />
                                            <select name="supplier_id" class="selectpicker form-control" data-live-search="true" id="supplier-id" data-live-search-style="begins" title="Select supplier...">
                                                @foreach($lims_supplier_list as $supplier)
                                                <option value="{{$supplier->id}}">{{$supplier->name .' ('. $supplier->company_name .')'}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.customer')}}</label>
                                            <input type="hidden" name="customer_id_hidden" value="{{ $lims_purchase_data->customer_id }}" />
                                            <select name="customer_id" class="selectpicker form-control" data-live-search="true" id="customer-id" data-live-search-style="begins" title="Select customer...">
                                                @foreach($lims_customer_list as $customer)
                                                <option value="{{$customer->id}}">{{$customer->name .' ('. $customer->company_name .')'}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                	<div class="col-md-4">
                                        <div class="form-group">
                                            <!-- <label>{{trans('file.Purchase Status')}}</label> -->
                                            <input type="hidden" name="status_hidden" value="{{$lims_purchase_data->status}}">
                                            <input type="hidden" name="status" value="{{$lims_purchase_data->status}}">
                                            <!-- <select name="status" class="form-control">
                                                <option selected value="1">{{trans('file.Recieved')}}</option>
                                                <option value="2">{{trans('file.Partial')}}</option>
                                                <option value="3">{{trans('file.Pending')}}</option>
                                                <option value="4">{{trans('file.Ordered')}}</option>
                                            </select> -->
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Attach Document')}}</label> <i class="dripicons-question" data-toggle="tooltip" title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                            <input type="file" name="document" class="form-control" >
                                            @if($errors->has('extension'))
                                                <span>
                                                   <strong>{{ $errors->first('extension') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div> -->
                                    <div class="col-md-12 mt-3">
                                        <label>{{trans('file.Select Product')}}</label>
                                        <div class="search-box input-group">
                                            <button type="button" class="btn btn-secondary"><i class="fa fa-barcode"></i></button>
                                            <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="Please type product code and select..." class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-5">
                                    <div class="col-md-12">
                                        <h5>{{trans('file.Order Table')}} *</h5>
                                        <div class="table-responsive mt-3">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{trans('file.name')}}</th>
                                                        <th>{{trans('file.Code')}}</th>
                                                        <!-- <th>{{trans('file.Quantity')}}</th> -->
                                                        <th>{{trans('file.Weight')}}</th>
                                                        <th>{{trans('file.Wastage')}}</th>
                                                        <th class="recieved-product-qty d-none">{{trans('file.Recieved')}}</th>
                                                        {{-- <th>{{trans('file.Batch No')}}</th>
                                                        <th>{{trans('file.Expired Date')}}</th>
                                                        <th>{{trans('file.Net Unit Cost')}}</th>
                                                        <th>{{trans('file.Discount')}}</th>
                                                        <th>{{trans('file.Tax')}}</th>
                                                        <th>{{trans('file.Subtotal')}}</th> --}}
                                                        <th><i class="dripicons-trash"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                        $temp_unit_name = [];
                                                        $temp_unit_operator = [];
                                                        $temp_unit_operation_value = [];
                                                    ?>
                                                    @foreach($lims_product_purchase_data as $product_purchase)
                                                    <tr>
                                                    <?php
                                                        $product_data = DB::table('products')->find($product_purchase->product_id);
                                                        if($product_purchase->variant_id) {
                                                            $product_variant_data = \App\ProductVariant::FindExactProduct($product_data->id, $product_purchase->variant_id)->select('item_code')->first();
                                                            $product_data->code = $product_variant_data->item_code;
                                                        }

                                                        $tax = DB::table('taxes')->where('rate', $product_purchase->tax_rate)->first();

                                                        // $units = DB::table('units')->where('base_unit', $product_data->unit_id)->orWhere('id', $product_data->unit_id)->get();
                                                        $purchase_unit_data = DB::table('units')->where('id', $product_purchase->purchase_unit_id)->first();
                                                        $purchase_unit_data_2 = DB::table('units')->where('id', $product_purchase->purchase_unit_id_2)->first();
                                                        $purchase_unit_data_3 = DB::table('units')->where('id', $product_purchase->purchase_unit_id_3)->first();

                                                        $units = DB::table('units')->get();

                                                        $unit_name = array();
                                                        $unit_operator = array();
                                                        $unit_operation_value = array();

                                                        foreach($units as $unit) {
                                                            // if($product_purchase->purchase_unit_id == $unit->id) {
                                                            //     array_unshift($unit_name, $unit->unit_name);
                                                            //     array_unshift($unit_operator, $unit->operator);
                                                            //     array_unshift($unit_operation_value, $unit->operation_value);
                                                            // }
                                                            // else {
                                                                $unit_name[]  = $unit->unit_name;
                                                                $unit_operator[] = $unit->operator;
                                                                $unit_operation_value[] = $unit->operation_value;
                                                            // }
                                                        }
                                                        if($product_data->tax_method == 1){
                                                            $product_cost = ($product_purchase->net_unit_cost + ($product_purchase->discount / $product_purchase->qty)) / $unit_operation_value[0];
                                                        }
                                                        else{
                                                            $product_cost = (($product_purchase->total + ($product_purchase->discount / $product_purchase->qty)) / $product_purchase->qty) / $unit_operation_value[0];
                                                        }


                                                        $temp_unit_name = $unit_name = implode(",",$unit_name) . ',';

                                                        $temp_unit_operator = $unit_operator = implode(",",$unit_operator) .',';

                                                        $temp_unit_operation_value = $unit_operation_value =  implode(",",$unit_operation_value) . ',';

                                                        $product_batch_data = \App\ProductBatch::select('batch_no', 'expired_date')->find($product_purchase->product_batch_id);
                                                    ?>
                                                        <td width="50%" class="prod-name">{{"$product_data->name".' ( '."$purchase_unit_data->unit_name".', '."$product_purchase->purchase_unit_value_2".' '."$purchase_unit_data_2->unit_name".' X '."$product_purchase->purchase_unit_value_3".' '."$purchase_unit_data_3->unit_name".' )' }} <button type="button" class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal"> <i class="dripicons-document-edit"></i></button> </td>
                                                        <td>{{$product_data->code}}</td>
                                                        <td><input type="number" class="form-control qty" name="qty[]" value="{{$product_purchase->qty}}" step="any" required /></td>
                                                        <td class="recieved-product-qty d-none"><input type="number" class="form-control recieved" name="recieved[]" value="{{$product_purchase->recieved}}" step="any"/></td>
                                                        <td><input type="number" class="form-control wstg" name="wastage[]" value="{{$product_purchase->wastage}}" step="any" required /></td>
                                                        @if($product_purchase->product_batch_id)
                                                        <!-- <td>
                                                            <input type="hidden" name="product_batch_id[]" value="{{$product_purchase->product_batch_id}}">
                                                            <input type="text" class="form-control batch-no" name="batch_no[]" value="{{$product_batch_data->batch_no}}" required/>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control expired-date" name="expired_date[]" value="{{$product_batch_data->expired_date}}" required/>
                                                        </td> -->
                                                        @else
                                                        <!-- <td>
                                                            <input type="hidden" name="product_batch_id[]">
                                                            <input type="text" class="form-control batch-no" name="batch_no[]" disabled />
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control expired-date" name="expired_date[]" disabled />
                                                        </td> -->
                                                        @endif
                                                        <!-- <td class="net_unit_cost">{{ number_format((float)$product_purchase->net_unit_cost, 2, '.', '') }} </td>
                                                        <td class="discount">{{ number_format((float)$product_purchase->discount, 2, '.', '') }}</td>
                                                        <td class="tax">{{ number_format((float)$product_purchase->tax, 2, '.', '') }}</td>
                                                        <td class="sub-total">{{ number_format((float)$product_purchase->total, 2, '.', '') }}</td> -->
                                                        <td><button type="button" class="ibtnDel btn btn-md btn-danger">{{trans("file.delete")}}</button></td>
                                                        <input type="hidden" class="product-id" name="product_id[]" value="{{$product_data->id}}"/>
                                                        <input type="hidden" class="product-name" name="product_name[]" value="{{$product_data->name}}"/>
                                                        <input type="hidden" class="product-code" name="product_code[]" value="{{$product_data->code}}"/>
                                                        <input type="hidden" class="product-cost" name="product_cost[]" value="{{ $product_cost}}"/>
                                                        <!-- <input type="hidden" class="purchase-unit" name="purchase_unit[]" value="{{$unit_name}}"/> -->
                                                        <input type="hidden" class="purchase-unit-all" name="purchase_unit_all[]" value="{{$unit_name}}"/>
                                                        <input type="hidden" class="purchase-unit" name="purchase_unit[]" value="{{$purchase_unit_data->unit_name}}"/>
                                                        <input type="hidden" class="purchase-unit_2" name="purchase_unit_2[]" value="{{$purchase_unit_data_2->unit_name}}"/>
                                                        <input type="hidden" class="purchase-unit_value_2" name="purchase_unit_value_2[]" value="{{$product_purchase->purchase_unit_value_2}}"/>
                                                        <input type="hidden" class="purchase-unit_3" name="purchase_unit_3[]" value="{{$purchase_unit_data_3->unit_name}}"/>
                                                        <input type="hidden" class="purchase-unit_value_3" name="purchase_unit_value_3[]" value="{{$product_purchase->purchase_unit_value_3}}"/>
                                                        <input type="hidden" class="purchase-unit-operator" value="{{$unit_operator}}"/>
                                                        <input type="hidden" class="purchase-unit-operation-value" value="{{$unit_operation_value}}"/>
                                                        <input type="hidden" class="net_unit_cost" name="net_unit_cost[]" value="{{$product_purchase->net_unit_cost}}" />
                                                        <input type="hidden" class="discount-value" name="discount[]" value="{{$product_purchase->discount}}" />
                                                        <input type="hidden" class="tax-rate" name="tax_rate[]" value="{{$product_purchase->tax_rate}}"/>
                                                        @if($tax)
                                                        <input type="hidden" class="tax-name" value="{{$tax->name}}" />
                                                        @else
                                                        <input type="hidden" class="tax-name" value="No Tax" />
                                                        @endif
                                                        <input type="hidden" class="tax-method" value="{{$product_data->tax_method}}"/>
                                                        <input type="hidden" class="tax-value" name="tax[]" value="{{$product_purchase->tax}}" />
                                                        <input type="hidden" class="subtotal-value" name="subtotal[]" value="{{$product_purchase->total}}" />
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="tfoot active">
                                                    <th colspan="2">{{trans('file.Total')}}</th>
                                                    <th id="total-qty">{{$lims_purchase_data->total_qty}}</th>
                                                    <th id="total-wastage">{{$lims_purchase_data->total_wastage}}</th>
                                                    {{-- <th></th>
                                                    <th></th>
                                                    <th></th>
                                                    <th class="recieved-product-qty d-none"></th>
                                                    <th id="total-discount">{{ number_format((float)$lims_purchase_data->total_discount, 2, '.', '') }}</th>
                                                    <th id="total-tax">{{ number_format((float)$lims_purchase_data->total_tax, 2, '.', '')}}</th>
                                                    <th id="total">{{ number_format((float)$lims_purchase_data->total_cost, 2, '.', '') }}</th> --}}
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_qty" value="{{$lims_purchase_data->total_qty}}" />
                                            <input type="hidden" name="total_wastage" value="{{$lims_purchase_data->total_wastage}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_discount" value="{{$lims_purchase_data->total_discount}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_tax" value="{{$lims_purchase_data->total_tax}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_cost" value="{{$lims_purchase_data->total_cost}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="item" value="{{$lims_purchase_data->item}}" />
                                            <input type="hidden" name="ttl_weight" value="{{$lims_purchase_data->total_qty}}" />
                                            <input type="hidden" name="order_tax" value="{{$lims_purchase_data->order_tax}}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="grand_total" value="{{$lims_purchase_data->grand_total}}" />
                                            <input type="hidden" name="paid_amount" value="{{$lims_purchase_data->paid_amount}}" />
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="row mt-5">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Order Tax')}}</label>
                                            <input type="hidden" name="order_tax_rate_hidden" value="{{$lims_purchase_data->order_tax_rate}}">
                                            <select class="form-control" name="order_tax_rate">
                                                <option value="0">{{trans('file.No Tax')}}</option>
                                                @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                <strong>{{trans('file.Discount')}}</strong>
                                            </label>
                                            <input type="number" name="order_discount" class="form-control" value="{{$lims_purchase_data->order_discount}}" step="any" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                <strong>{{trans('file.Shipping Cost')}}</strong>
                                            </label>
                                            <input type="number" name="shipping_cost" class="form-control" value="{{$lims_purchase_data->shipping_cost}}" step="any" />
                                        </div>
                                    </div>
                                </div> -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{trans('file.Note')}}</label>
                                            <textarea rows="5" class="form-control" name="note" >{{ $lims_purchase_data->note }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary" id="submit-button">
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <table class="table table-bordered table-condensed totals">
            <td><strong>{{trans('file.Items')}}</strong>
                <span class="pull-right" id="item">0.00</span>
            </td>
            <td><strong>{{trans('Total Weight')}}</strong>
                <span class="pull-right" id="total_weight">0</span>
            </td>
            <td><strong>{{trans('Total Wastage')}}</strong>
                <span class="pull-right" id="total_wastage">0</span>
            </td>
            <!-- <td><strong>{{trans('file.Total')}}</strong>
                <span class="pull-right" id="subtotal">0.00</span>
            </td>
            <td><strong>{{trans('file.Order Tax')}}</strong>
                <span class="pull-right" id="order_tax">0.00</span>
            </td>
            <td><strong>{{trans('file.Order Discount')}}</strong>
                <span class="pull-right" id="order_discount">0.00</span>
            </td>
            <td><strong>{{trans('file.Shipping Cost')}}</strong>
                <span class="pull-right" id="shipping_cost">0.00</span>
            </td>
            <td><strong>{{trans('file.grand total')}}</strong>
                <span class="pull-right" id="grand_total">0.00</span>
            </td> -->
        </table>
    </div>
    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modal-header" class="modal-title"></h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <form>
                        <input type="hidden" name="edit_qty" class="form-control" step="any">
                        <!-- <div class="form-group">
                            <label>{{trans('file.Quantity')}}</label>
                        </div> -->
                        <!-- <div class="form-group">
                            <label>{{trans('file.Unit Discount')}}</label>
                            <input type="number" name="edit_discount" class="form-control" step="any">
                        </div> -->
                        <!-- <div class="form-group">
                            <label>{{trans('file.Unit Cost')}}</label>
                            <input type="number" name="edit_unit_cost" class="form-control" step="any">
                        </div> -->
                        <?php
                $tax_name_all[] = 'No Tax';
                $tax_rate_all[] = 0;
                foreach($lims_tax_list as $tax) {
                    $tax_name_all[] = $tax->name;
                    $tax_rate_all[] = $tax->rate;
                }
            ?>
                            <!-- <div class="form-group">
                                <label>{{trans('file.Tax Rate')}}</label>
                                <select name="edit_tax_rate" class="form-control selectpicker">
                                    @foreach($tax_name_all as $key => $name)
                                    <option value="{{$key}}">{{$name}}</option>
                                    @endforeach
                                </select>
                            </div> -->
                            <div class="form-group">
                                <!-- <label>{{trans('file.Product Unit')}}</label> -->
                                <label>{{'Color'}}</label>
                                <select name="edit_unit" class="form-control selectpicker">
                                    <option value="1">Any</option>
                                    <option value="2">Red</option>
                                    <option value="3">Blue</option>
                                    <option value="4">Green</option>
                                    <option value="5">White</option>
                                    <option value="6">Black</option>
                                </select>
                            </div>

                            <div  class="row">
                                <div  class="col-6">
                                    <div class="form-group">
                                        <label>{{'Length Number'}}</label>
                                        <input type="number" name="edit_unit_value_2" class="form-control" value="" />
                                    </div>
                                </div>
                                <div  class="col-6">
                                    <div class="form-group">
                                        <label>{{'Length Unit'}}</label>
                                        <select name="edit_unit_2" class="form-control selectpicker">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div  class="row">
                                <div  class="col-6">
                                    <div class="form-group">
                                        <label>{{'Width Number'}}</label>
                                        <input type="number" name="edit_unit_value_3" class="form-control" value="" />
                                    </div>
                                </div>
                                <div  class="col-6">
                                    <div class="form-group">
                                        <label>{{'Width Unit'}}</label>
                                        <select name="edit_unit_3" class="form-control selectpicker">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="button" name="update_btn" class="btn btn-primary">{{trans('file.update')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">

    $("ul#purchase").siblings('a').addClass("active");
    $("ul#purchase").addClass("show");

// array data depend on warehouse
var lims_product_array = [];
var product_code = [];
var product_name = [];
var product_qty = [];
var product_wastage = [];

// array data with selection
var product_cost = [];
var product_discount = [];
var tax_rate = [];
var tax_name = [];
var tax_method = [];

var unit_name = [];
var unit_name_2 = [];
var unit_value_2 = [];
var unit_name_3 = [];
var unit_value_3 = [];
var unit_operator = [];
var unit_operation_value = [];

// temporary array
var temp_unit_name = [];
var temp_unit_operator = [];
var temp_unit_operation_value = [];

var rowindex;
var customer_group_rate;
var row_product_cost;

var rownumber = $('table.order-list tbody tr:last').index();

for(rowindex  =0; rowindex <= rownumber; rowindex++){

    product_cost.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-cost').val()));
    var total_discount = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text());
    var quantity = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
    var wastage = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.wstg').val());
    product_discount.push((total_discount / quantity).toFixed(2));
    tax_rate.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val()));
    tax_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-name').val());
    tax_method.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-method').val());
    temp_unit_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit-all').val().split(',');
    unit_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val());
    unit_name_2.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_2').val());
    unit_value_2.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_value_2').val());
    unit_name_3.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_3').val());
    unit_value_3.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_value_3').val());
    unit_operator.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit-operator').val());
    unit_operation_value.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit-operation-value').val());
    // $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val(temp_unit_name[0]);
}

$('.selectpicker').selectpicker({
    style: 'btn-link',
});

$('[data-toggle="tooltip"]').tooltip();

//assigning value
$('select[name="supplier_id"]').val($('input[name="supplier_id_hidden"]').val());
$('select[name="warehouse_id"]').val($('input[name="warehouse_id_hidden"]').val());
$('select[name="status"]').val($('input[name="status_hidden"]').val());
$('select[name="order_tax_rate"]').val($('input[name="order_tax_rate_hidden"]').val());
$('select[name="purchase_status"]').val($('input[name="purchase_status_hidden"]').val());
$('.selectpicker').selectpicker('refresh');

$('#item').text($('input[name="item"]').val()
//  + '(' + $('input[name="total_qty"]').val() + ')'
);
$('#total_weight').text($('input[name="ttl_weight"]').val());
$('#total_wastage').text($('input[name="total_wastage"]').val());
$('#subtotal').text(parseFloat($('input[name="total_cost"]').val()).toFixed(2));
$('#order_tax').text(parseFloat($('input[name="order_tax"]').val()).toFixed(2));
if($('select[name="status"]').val() == 2){
    $(".recieved-product-qty").removeClass("d-none");

}
if(!$('input[name="order_discount"]').val())
    $('input[name="order_discount"]').val('0.00');
$('#order_discount').text(parseFloat($('input[name="order_discount"]').val()).toFixed(2));
if(!$('input[name="shipping_cost"]').val())
    $('input[name="shipping_cost"]').val('0.00');
$('#shipping_cost').text(parseFloat($('input[name="shipping_cost"]').val()).toFixed(2));
$('#grand_total').text(parseFloat($('input[name="grand_total"]').val()).toFixed(2));

$('select[name="status"]').on('change', function() {
    if($('select[name="status"]').val() == 2){
        $(".recieved-product-qty").removeClass("d-none");
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val($(this).val());
        });

    }
    else if(($('select[name="status"]').val() == 3) || ($('select[name="status"]').val() == 4)){
        $(".recieved-product-qty").addClass("d-none");
        $(".recieved").each(function() {
            $(this).val(0);
        });
    }
    else {
        $(".recieved-product-qty").addClass("d-none");
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val($(this).val());
        });
    }
});


var lims_product_code = [
    @foreach($lims_product_list_without_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->code . ' (' . $product->name . ')');
        ?>
    @endforeach
    @foreach($lims_product_list_with_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->item_code . ' (' . $product->name . ')');
        ?>
    @endforeach
        <?php
        echo  '"'.implode('","', $productArray).'"';
        ?>
];

    var lims_productcodeSearch = $('#lims_productcodeSearch');

    lims_productcodeSearch.autocomplete({
    source: function(request, response) {
        var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
        response($.grep(lims_product_code, function(item) {
            return matcher.test(item);
        }));
    },
    response: function(event, ui) {
        if (ui.content.length == 1) {
            var data = ui.content[0].value;
            $(this).autocomplete( "close" );
            productSearch(data);
        };
    },
    select: function(event, ui) {
        var data = ui.item.value;
        productSearch(data);
    }
});

$('body').on('focus',".expired-date", function() {
    $(this).datepicker({
        format: "yyyy-mm-dd",
        startDate: "<?php echo date("Y-m-d", strtotime('+ 1 days')) ?>",
        autoclose: true,
        todayHighlight: true
    });
});

//Change quantity
$("#myTable").on('input', '.qty', function() {
    rowindex = $(this).closest('tr').index();
    if($(this).val() < 1 && $(this).val() != '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(1);
      alert("Quantity can't be less than 1");
    }
    checkQuantity($(this).val(), true);
});
//Change wastage
$("#myTable").on('input', '.wstg', function() {
    rowindex = $(this).closest('tr').index();
    if($(this).val() < 0 && $(this).val() != '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .wstg').val(0);
      alert("Wastage can't be less than 0");
    }
    calculateTotal();
});


//Delete product
$("table.order-list tbody").on("click", ".ibtnDel", function(event) {
    rowindex = $(this).closest('tr').index();
    product_cost.splice(rowindex, 1);
    product_discount.splice(rowindex, 1);
    tax_rate.splice(rowindex, 1);
    tax_name.splice(rowindex, 1);
    tax_method.splice(rowindex, 1);
    unit_name.splice(rowindex, 1);
    unit_name_2.splice(rowindex, 1);
    unit_value_2.splice(rowindex, 1);
    unit_name_3.splice(rowindex, 1);
    unit_value_3.splice(rowindex, 1);
    unit_operator.splice(rowindex, 1);
    unit_operation_value.splice(rowindex, 1);
    $(this).closest("tr").remove();
    calculateTotal();
});

//Edit product
$("table.order-list").on("click", ".edit-product", function() {
    rowindex = $(this).closest('tr').index();
    var row_product_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1)').text();
    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
    $('#modal_header').text(row_product_name + '(' + row_product_code + ')');

    var qty = $(this).closest('tr').find('.qty').val();
    $('input[name="edit_qty"]').val(qty);

    $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed(2));

    unitConversion();
    // $('input[name="edit_unit_cost"]').val(row_product_cost.toFixed(2));

    var tax_name_all = <?php echo json_encode($tax_name_all) ?>;
    var pos = tax_name_all.indexOf(tax_name[rowindex]);
    $('select[name="edit_tax_rate"]').val(pos);

    // color_units = ['Any', 'Red', 'Blue', 'Green', 'White', 'Black' ];
    color_units = temp_unit_name.slice(0, 6);
    // temp_unit_name = (unit_name[rowindex]).split(',');
    // temp_unit_name.pop();
    temp2_unit_name = temp_unit_name;
    width_units = temp_unit_name.slice(6, 14);
    // console.log(width_units);
    // length_units = temp2_unit_name.splice(0, 6);
    // width_units = temp2_unit_name.splice(0, 6);
    length_units = width_units;

    temp_unit_operator = (unit_operator[rowindex]).split(',');
    // temp_unit_operator.pop();
    temp_unit_operation_value = (unit_operation_value[rowindex]).split(',');
    // temp_unit_operation_value.pop();

    $purchase_unit = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val();
    $purchase_unit_2 = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_2').val();
    $purchase_unit_value_2 = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_value_2').val();
    $purchase_unit_3 = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_3').val();
    $purchase_unit_value_3 = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_value_3').val();

    // console.log($purchase_unit, $purchase_unit_2, $purchase_unit_3);

    $('select[name="edit_unit"]').empty();
    $.each(color_units, function(key, value) {
        if($purchase_unit == value){
            $('select[name="edit_unit"]').append('<option selected value="' + key + '">' + value + '</option>');
        }
        else{
            $('select[name="edit_unit"]').append('<option value="' + key + '">' + value + '</option>');
        }
    });
    $('input[name="edit_unit_value_2"]').val($purchase_unit_value_2);
    $('select[name="edit_unit_2"]').empty();
    $.each(length_units, function(key, value) {
        if($purchase_unit_2 == value){
            $('select[name="edit_unit_2"]').append('<option selected value="' + key + '">' + value + '</option>');
        }
        else{
            $('select[name="edit_unit_2"]').append('<option value="' + key + '">' + value + '</option>');
        }
    });
    $('input[name="edit_unit_value_3"]').val($purchase_unit_value_3);
    $('select[name="edit_unit_3"]').empty();
    $.each(width_units, function(key, value) {
        if($purchase_unit_3 == value){
            $('select[name="edit_unit_3"]').append('<option selected value="' + key + '">' + value + '</option>');
        }
        else{
            $('select[name="edit_unit_3"]').append('<option value="' + key + '">' + value + '</option>');
        }
    });
    // $('select[name="edit_unit"] option:selected').text();
    // $('select[name="edit_unit"] option:selected').val();
    $('.selectpicker').selectpicker('refresh');
});

//Update product
$('button[name="update_btn"]').on("click", function() {
    var edit_discount = $('input[name="edit_discount"]').val();
    var edit_qty = $('input[name="edit_qty"]').val();
    // var edit_unit_cost = $('input[name="edit_unit_cost"]').val();

    // if (parseFloat(edit_discount) > parseFloat(edit_unit_cost)) {
    //     alert('Invalid Discount Input!');
    //     return;
    // }

    if(edit_qty < 1) {
        $('input[name="edit_qty"]').val(1);
        edit_qty = 1;
        alert("Quantity can't be less than 1");
    }

    var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
    var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));
    row_unit_operation_value = parseFloat(row_unit_operation_value);
    var tax_rate_all = <?php echo json_encode($tax_rate_all) ?>;


    tax_rate[rowindex] = parseFloat(tax_rate_all[$('select[name="edit_tax_rate"]').val()]);
    tax_name[rowindex] = $('select[name="edit_tax_rate"] option:selected').text();


    // if (row_unit_operator == '*') {
    //     product_cost[rowindex] = $('input[name="edit_unit_cost"]').val() / row_unit_operation_value;
    // } else {
    //     product_cost[rowindex] = $('input[name="edit_unit_cost"]').val() * row_unit_operation_value;
    // }

    product_discount[rowindex] = $('input[name="edit_discount"]').val();
    var position = $('select[name="edit_unit"]').val();
    var position_2 = 6 + Number($('select[name="edit_unit_2"]').val());
    var position_3 = 6 + Number($('select[name="edit_unit_3"]').val());
    var unit_value_2 = $('input[name="edit_unit_value_2"]').val();
    var unit_value_3 = $('input[name="edit_unit_value_3"]').val();
    var temp_operator = temp_unit_operator[position];
    var temp_operation_value = temp_unit_operation_value[position];

    $prod_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-name').val();
    $prod_name_text = $prod_name + ' ( ' + temp_unit_name[position] + ', ' + unit_value_2 + ' ' + temp_unit_name[position_2] + ' X ' + unit_value_3 + ' ' + temp_unit_name[position_3] + ' )' + '<button type="button" class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal"> <i class="dripicons-document-edit"></i></button>';

    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.prod-name').html($prod_name_text);
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val(temp_unit_name[position]);
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_2').val(temp_unit_name[position_2]);
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_value_2').val(unit_value_2);
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_3').val(temp_unit_name[position_3]);
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit_value_3').val(unit_value_3);

    // temp_unit_name.splice(position, 1);
    // temp_unit_operator.splice(position, 1);
    // temp_unit_operation_value.splice(position, 1);

    // temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
    // temp_unit_operator.unshift(temp_operator);
    // temp_unit_operation_value.unshift(temp_operation_value);

    // unit_name[rowindex] = temp_unit_name.toString() + ',';
    // unit_operator[rowindex] = temp_unit_operator.toString() + ',';
    // unit_operation_value[rowindex] = temp_unit_operation_value.toString() + ',';
    checkQuantity(edit_qty, false);
});

function productSearch(data) {
    $.ajax({
        type: 'GET',
        url: '../lims_product_search',
        data: {
            data: data
        },
        success: function(data) {
            var flag = 1;
            // $(".product-code").each(function(i) {
            //     if ($(this).val() == data[1]) {
            //         rowindex = i;
            //         var qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) + 1;
            //         $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
            //         $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .recieved').val(qty);
            //         calculateRowProductData(qty);
            //         flag = 0;
            //     }
            // });
            $("input[name='product_code_name']").val('');
            if(flag){
                var newRow = $("<tr>");
                var cols = '';
                temp_unit_name = (data[6]).split(',');
                cols += '<td width="50%" class="prod-name" >' + data[0] + '<button type="button" class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal"> <i class="dripicons-document-edit"></i></button></td>';
                cols += '<td>' + data[1] + '</td>';
                cols += '<td><input type="number" class="form-control qty" name="qty[]" value="1" step="any" required /></td>';
                if($('select[name="status"]').val() == 1)
                    cols += '<td class="recieved-product-qty d-none"><input type="number" class="form-control recieved" name="recieved[]" value="1" step="any" /></td>';
                else if($('select[name="status"]').val() == 2)
                    cols += '<td class="recieved-product-qty"><input type="number" class="form-control recieved" name="recieved[]" value="1" step="any"/></td>';
                else
                    cols += '<td class="recieved-product-qty d-none"><input type="number" class="form-control recieved" name="recieved[]" value="0" step="any"/></td>';
                if(data[10]) {
                    // cols += '<td><input type="text" class="form-control batch-no" name="batch_no[]" required/></td>';
                    // cols += '<td><input type="text" class="form-control expired-date" name="expired_date[]" required/></td>';
                }
                else {
                    // cols += '<td><input type="text" class="form-control batch-no" name="batch_no[]" disabled/></td>';
                    // cols += '<td><input type="text" class="form-control expired-date" name="expired_date[]" disabled/></td>';
                }
                cols += '<td><input type="number" class="form-control wstg" name="wastage[]" value="0" step="any" required /></td>';

                // cols += '<td class="net_unit_cost"></td>';
                // cols += '<td class="discount">0.00</td>';
                // cols += '<td class="tax"></td>';
                // cols += '<td class="sub-total"></td>';
                cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger">{{trans("file.delete")}}</button></td>';
                cols += '<input type="hidden" class="product-name" name="product_name[]" value="' + data[0] + '"/>';
                cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + data[1] + '"/>';
                cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[9] + '"/>';
                cols += '<input type="hidden" class="purchase-unit" name="purchase_unit[]" value="' + temp_unit_name[0] + '"/>';
                cols += '<input type="hidden" class="net_unit_cost" name="net_unit_cost[]" value="' + 0 + '"/>';
                cols += '<input type="hidden" class="purchase-unit_2" name="purchase_unit_2[]" value="' + temp_unit_name[6] + '"/>';
                cols += '<input type="hidden" class="purchase-unit_value_2" name="purchase_unit_value_2[]" value="' + 0 + '"/>';
                cols += '<input type="hidden" class="purchase-unit_3" name="purchase_unit_3[]" value="' + temp_unit_name[6] + '"/>';
                cols += '<input type="hidden" class="purchase-unit_value_3" name="purchase_unit_value_3[]" value="' + 0 + '"/>';
                cols += '<input type="hidden" class="discount-value" name="discount[]" />';
                cols += '<input type="hidden" class="tax-rate" name="tax_rate[]" value="' + data[3] + '"/>';
                cols += '<input type="hidden" class="tax-value" name="tax[]" />';
                cols += '<input type="hidden" class="subtotal-value" name="subtotal[]" />';

                newRow.append(cols);
                $("table.order-list tbody").prepend(newRow);

                rowindex = newRow.index();
                product_cost.splice(rowindex, 0, parseFloat(data[2]));
                product_discount.splice(rowindex, 0, '0.00');
                tax_rate.splice(rowindex, 0, parseFloat(data[3]));
                tax_name.splice(rowindex, 0, data[4]);
                tax_method.splice(rowindex, 0, data[5]);
                unit_name.splice(rowindex, 0, data[6]);
                unit_name_2.splice(rowindex, 0, data[6]);
                unit_value_2.splice(rowindex, 0, 0);
                unit_name_3.splice(rowindex, 0, data[6]);
                unit_value_3.splice(rowindex, 0, 0);
                unit_operator.splice(rowindex, 0, data[7]);
                unit_operation_value.splice(rowindex, 0, data[8]);
                calculateRowProductData(1);
            }
        }
    });
}
function checkQuantity(purchase_qty, flag) {
    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
    var pos = product_code.indexOf(row_product_code);
    var operator = unit_operator[rowindex].split(',');
    var operation_value = unit_operation_value[rowindex].split(',');
    if(operator[0] == '*')
    	total_qty = purchase_qty * operation_value[0];
    else if(operator[0] == '/')
    	total_qty = purchase_qty / operation_value[0];

    $('#editModal').modal('hide');
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(purchase_qty);
    var status = $('select[name="status"]').val();
    if(status == '1' || status == '2' )
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val(purchase_qty);

    // calculateRowProductData(purchase_qty);
    calculateTotal();
}

function calculateRowProductData(quantity) {
    unitConversion();
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text((product_discount[rowindex] * quantity).toFixed(2));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount-value').val((product_discount[rowindex] * quantity).toFixed(2));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val(tax_rate[rowindex].toFixed(2));

    if (tax_method[rowindex] == 1) {
        var net_unit_cost = row_product_cost - product_discount[rowindex];
        var tax = net_unit_cost * quantity * (tax_rate[rowindex] / 100);
        var sub_total = (net_unit_cost * quantity) + tax;

        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_cost').text(net_unit_cost.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_cost').val(net_unit_cost.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax').text(tax.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(sub_total.toFixed(2));
    } else {
        var sub_total_unit = row_product_cost - product_discount[rowindex];
        var net_unit_cost = (100 / (100 + tax_rate[rowindex])) * sub_total_unit;
        var tax = (sub_total_unit - net_unit_cost) * quantity;
        var sub_total = sub_total_unit * quantity;

        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_cost').text(net_unit_cost.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_cost').val(net_unit_cost.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax').text(tax.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total.toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(sub_total.toFixed(2));
    }

    calculateTotal();
}

function unitConversion() {
    var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
    var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));
    row_unit_operation_value = parseFloat(row_unit_operation_value);
    if (row_unit_operator == '*') {
        row_product_cost = product_cost[rowindex] * row_unit_operation_value;
    } else {
        row_product_cost = product_cost[rowindex] / row_unit_operation_value;
    }
}

function calculateTotal() {
    //Sum of quantity
    var total_qty = 0;
    var total_wastage = 0;

    $(".qty").each(function() {

        if ($(this).val() == '') {
            total_qty += 0;
        } else {
            total_qty += parseFloat($(this).val());
        }
    });
    $("#total-qty").text(total_qty);
    $('input[name="total_qty"]').val(total_qty);

    $(".wstg").each(function() {

        if ($(this).val() == '') {
            total_wastage += 0;
        } else {
            total_wastage += parseFloat($(this).val());
        }
    });
    $("#total-wastage").text(total_wastage);
    $('input[name="total_wastage"]').val(total_wastage);

    //Sum of discount
    var total_discount = 0;
    $(".discount").each(function() {
        total_discount += parseFloat($(this).text());
    });
    $("#total-discount").text(total_discount.toFixed(2));
    $('input[name="total_discount"]').val(total_discount.toFixed(2));

    //Sum of tax
    var total_tax = 0;
    $(".tax").each(function() {
        total_tax += parseFloat($(this).text());
    });
    $("#total-tax").text(total_tax.toFixed(2));
    $('input[name="total_tax"]').val(total_tax.toFixed(2));

    //Sum of subtotal
    var total = 0;
    $(".sub-total").each(function() {
        total += parseFloat($(this).text());
    });
    $("#total").text(total.toFixed(2));
    $('input[name="total_cost"]').val(total.toFixed(2));

    calculateGrandTotal();
}

function calculateGrandTotal() {

    var item = $('table.order-list tbody tr:last').index() + 1;

    var total_qty = parseFloat($('#total-qty').text());
    var total_wastage = parseFloat($('#total-wastage').text());
    // var subtotal = parseFloat($('#total').text());
    var subtotal = parseFloat('0.00');
    // var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
    var order_tax = parseFloat('0');
    var order_discount = parseFloat($('input[name="order_discount"]').val());
    var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());

    if (!order_discount)
        order_discount = 0.00;
    if (!shipping_cost)
        shipping_cost = 0.00;

    // item = ++item + '(' + total_qty + ')';
    order_tax = (subtotal - order_discount) * (order_tax / 100);
    // var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;
    var grand_total = subtotal;

    $('#item').text(item);
    $('#total_weight').text(total_qty);
    $('#total_wastage').text(total_wastage);
    $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
    $('#subtotal').text(subtotal.toFixed(2));
    $('#order_tax').text(order_tax.toFixed(2));
    $('input[name="order_tax"]').val(order_tax.toFixed(2));
    $('#order_discount').text(order_discount.toFixed(2));
    $('#shipping_cost').text(shipping_cost.toFixed(2));
    $('#grand_total').text(grand_total.toFixed(2));
    $('input[name="grand_total"]').val(grand_total.toFixed(2));
}

$('input[name="order_discount"]').on("input", function() {
    calculateGrandTotal();
});

$('input[name="shipping_cost"]').on("input", function() {
    calculateGrandTotal();
});

$('select[name="order_tax_rate"]').on("change", function() {
    calculateGrandTotal();
});

$(window).keydown(function(e){
    if (e.which == 13) {
        var $targ = $(e.target);
        if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
            var focusNext = false;
            $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                if (this === e.target) {
                    focusNext = true;
                }
                else if (focusNext){
                    $(this).focus();
                    return false;
                }
            });
            return false;
        }
    }
});

$('#purchase-form').on('submit',function(e){
    var rownumber = $('table.order-list tbody tr:last').index();
    if (rownumber < 0) {
        alert("Please insert product to order table!")
        e.preventDefault();
    }

    // else if($('select[name="status"]').val() != 1)
    else if($('input[name="status"]').val() != 1)
    {
        flag = 0;
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            quantity =  $(this).val();
            recieved = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val();

            if(quantity != recieved){
                flag = 1;
                return false;
            }
        });
        if(!flag){
            alert('Quantity and Recieved value is same! Please Change Purchase Status or Recieved value');
            e.preventDefault();
        }
    }
    else
        $(".batch-no, .expired-date").prop('disabled', false);
});
</script>
@endsection
