@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@section('content')
                <div class="row">
                <div class="col-xl-3 col-lg-6 col-12 col-md-6">
                        <div class="card stretchClass">
                            <div class="card-content">
                                <div class="media align-items-stretch ">
                                    <div class="p-2 text-center bg-primary bg-darken-2">
                                        <i class="ficon p-1 icon-bar-chart customize-icon font-large-2 white"></i>
                                    </div>
                                    <div class="p-2 bg-gradient-x-primary white media-body">
                                        <h5>Sales</h5>
                                        <h5 class="text-bold-400 mb-0">{{env('CURRENCY_SYMBOL', '£')}} {{$blocks['sales_today'][0]->amount}}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6 col-12 col-md-6">
                        <div class="card stretchClass">
                            <div class="card-content">
                                <div class="media align-items-stretch ">
                                    <div class="p-2 text-center bg-success bg-darken-2">
                                        <i class="icon-wallet font-large-2 white"></i>
                                    </div>
                                    <div class="p-2 bg-gradient-x-success white media-body">
                                        <h5>Purchase</h5>
                                        <h5 class="text-bold-400 mb-0"> {{env('CURRENCY_SYMBOL', '£')}} {{$blocks['purchase_today'][0]->amount}}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-12 col-md-6">
                        <div class="card stretchClass">
                            <div class="card-content">
                                <div class="media align-items-stretch">
                                    <div class="p-2 text-center bg-warning bg-darken-2">
                                        <i class="icon-basket-loaded font-large-2 white"></i>
                                    </div>
                                    <div class="p-2 bg-gradient-x-warning white media-body">
                                        <h5>Today Orders</h5>
                                        <h5 class="text-bold-400 mb-0"> {{$blocks['today_orders'][0]->today_orders}}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-12 col-md-6">
                        <div class="card stretchClass">
                            <div class="card-content">
                                <div class="media align-items-stretch">
                                    <div class="p-2 text-center bg-danger bg-darken-2">
                                        <i class="icon-user font-large-2 white"></i>
                                    </div>
                                    <div class="p-2 bg-gradient-x-danger white media-body">
                                        <h5>Payments</h5>
                                        <h5 class="text-bold-400 mb-0"><i class="feather icon-arrow-down"></i> {{env('CURRENCY_SYMBOL', '£')}} {{$blocks['customers_today_payments'][0]->amount == 0 ? 0 : $blocks['customers_alltime_payments'][0]->amount}}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><br><br>
            <div class="row match-height">
                    <div class="col-xl-8 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Recent Orders</h4>
                                <a class="heading-elements-toggle"><i class="fa fa-ellipsis-v font-medium-3"></i></a>
                                <div class="heading-elements">
                                    <ul class="list-inline mb-0">
                                        <!--<li><a data-action="reload"><i class="feather icon-rotate-cw"></i></a></li>-->
                                        <li><a data-action="expand"><i class="feather icon-maximize"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="card-body">
									<div class="row">
										<div class="col-6">
											<h5>Total paid invoices {{$blocks['paid_unpaid_today'][0]->paid}}, unpaid {{$blocks['paid_unpaid_today'][0]->unpaid}}.</h5>
										</div>
										<div class="col-6 text-right">
										@if(hasPermissionMenu('daily_report.daily_book_sales.view.index'))
										  <a href="{{ route('daily_report.daily_book_sales.view.index') }}" target="_blank">Invoice Summary <i class="feather icon-arrow-right"></i></a>
										  @endif
										</div>
									</div>
								
									<!--<h5>Total paid invoices {{$blocks['paid_unpaid_today'][0]->paid}}, unpaid {{$blocks['paid_unpaid_today'][0]->unpaid}}. <span class="float-right">
									  @if(hasPermissionMenu('daily_report.daily_book_sales.view.index'))
									  <a href="{{ route('daily_report.daily_book_sales.view.index') }}" target="_blank">Invoice Summary <i class="feather icon-arrow-right"></i></a>
									  @endif
									</span>
									</h5>-->
                                </div>
                                <div class="table-responsive">
                                    <table id="users-list-datatable" class="table table-hover mb-0 ps-container ps-theme-default">
                                        <thead>
                                            <tr>
                                                <th>#Invoice</th>
                                                <th>Customer Name</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                          <!-- {{$invoices}} -->
                                          <?php $incrmeantId = 1; ?>
                                          @foreach($invoices as $key => $invoice)
										  <?php
										  $total = [];
										  foreach($invoice->products as $p){
											$total[] = $p->sub_total;
										  }
										  ?>
                                          @if($invoice->order && $invoice->order->total > 1)
                                          @if($incrmeantId < 6)
                                            <tr>
                                                  <td class="text-truncate">
                                                    @if(hasPermissionMenu('data_entry.sales_entry.invoice.index'))
                                                          <a href="{{ route('data_entry.sales_entry.invoice.index',['invoice' => $invoice->id]) }}">{{$invoice->id}}</a>
                                                    @else
                                                    {{$invoice->id}}
                                                    @endif
                                                  </td>
                                                  <td class="text-truncate"><a href="{{route('management.customers.edit.edit',['customer' => $invoice->customer->id])}}">{{$invoice->customer->name}}</a></td>
                                                  <td class="text-truncate">{{$invoice->created_at}}</td>

                                                  <td class="text-truncate">  @if($invoice->invoicePayment)
                                                        <!--@if($invoice->invoicePayment->payment_id == 1)
                                                        <span class="badge badge-success">  Cash </span>
                                                          @elseif($invoice->invoicePayment->payment_id == 2)
                                                        <span class="badge badge-primary">  Cheque </span>
                                                          @elseif($invoice->invoicePayment->payment_id == 3)
                                                        <span class="badge badge-danger">  Credit </span>
                                                          @elseif($invoice->invoicePayment->payment_id == 4)
                                                        <span class="badge badge-warning">  Online Transfer </span>
                                                         @endif-->
														<span class="badge {{$invoice->invoicePayment->payment->type == 'None' ? 'badge-danger' : 'badge-success'}}">{{$invoice->invoicePayment->payment->type}} </span>
                                                      @endif
                                                  </td>
                                                  <td class="text-truncate">@if($invoice->order)  {{env('CURRENCY_SYMBOL', '£')}} {{array_sum($total)}}  @endif</td>
                                              </tr>
                                              <?php $incrmeantId++; ?>
                                               @endif
                                               @endif

                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Recent Customers</h4>
                                <a class="heading-elements-toggle"><i class="fa fa-ellipsis-v font-medium-3"></i></a>
                                <div class="heading-elements">
                                    <!--<ul class="list-inline mb-0">
                                        <li><a data-action="reload"><i class="feather icon-rotate-cw"></i></a></li>
                                    </ul>-->
                                </div>
                            </div>
                            <div class="card-content px-1">
                                <div id="recent-buyers" class="media-list position-relative">
									@foreach($blocks['latest_customers'] as $c)
                                    <a href="#" class="media border-0">
                                        <div class="media-left pr-1">
                                            <div class="avatar avatar-online avatar-md">
												<img class="media-object rounded-circle" src="/img/dummy.jpg" alt="Generic placeholder image">
                                                <i></i>
                                            </div>
                                        </div>
                                        <div class="media-body w-100">
                                            <h6 class="list-group-item-heading">{{$c->name}} <!--<span class="font-medium-4 float-right pt-1">$1,021</span></h6>-->
                                            <!--<p class="list-group-item-text mb-0"><span class="badge badge-primary">Electronics</span><span class="badge badge-warning ml-1">Decor</span></p>
											-->
										</div>
                                    </a>
									@endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- <div id="react-task-app"></div>s -->
@endsection
