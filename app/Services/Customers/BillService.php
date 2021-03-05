<?php

namespace App\Services\Customers;

use App\Exports\Orders\OrderExportExcel;
use Illuminate\Support\Carbon;
use App\Http\Requests\Orders\CreateBillRequest;
use App\Models\Bill;
use App\Models\LogAccountant;
use App\Models\LogAdmin;
use App\Models\Order;
use App\Models\PaymentCustomer;
use App\Models\Product;
use App\Models\refundCustomerModel;
use Illuminate\Support\Facades\Auth;
use App\Models\Transport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillService
{
    public function __construct(OrderExportExcel $exportExcel)
    {
        $this->orderExportExcel = $exportExcel;
    }
    public function getALlBillByUname(Request $request)
    {
        $uname = Auth::user()->uname;
        $codeOrderByBill = Bill::select('Codeorder')->get()->toArray();
        $billcodes = DB::table('quanlythe')->where('Sohoadon', '!=', null)->select('Sohoadon')->distinct()->get()->toArray();
        // foreach ($codeOrderByBill as  $value) {
        //     $priceOrder = DB::table('oder')->where('codeorder', $value)->first();
        //     Bill::where('Codeorder', $value)->update([
        //         'PriceOut' => $priceOrder->total
        //     ]);
        // }
        // tính lại priceOut
        foreach ($codeOrderByBill as  $value) {
            $priceO = 0;
            $priceOrder = DB::table('product')->where('codeorder', $value)->get();
            foreach ($priceOrder as $item) {
                $priceO += $item->total;
                $uname2 = $item->uname;
            }
            Bill::where('Codeorder', $value)->update([
                'PriceOut' => $priceO,
                'uname' => $uname2
            ]);
        }
        foreach ($billcodes as $value) {
            $sumPriceIn = DB::table('quanlythe')->where('Sohoadon', $value->Sohoadon)->where('uname', $uname)->selectRaw('sum(price_in) as totalPriceIn')->first();
            Bill::where('So_Hoadon', $value->Sohoadon)->update([
                'PriceIn' => $sumPriceIn->totalPriceIn
            ]);
        }
        $So_Hoadon = $request->So_Hoadon;
        $Date_Create = $request->Date_Create;


        $bills = Bill::with('Order')->whereHas('Order', function ($query) use ($uname) {
            return $query->where('uname', $uname);
        });

        if (!empty($So_Hoadon)) {
            $bills = $bills->where('So_Hoadon', 'like', '%' . $So_Hoadon);
        }

        if (!empty($Date_Create)) {
            $bills = $bills->whereDate('Date_Create', $Date_Create);
        }
        $bills = $bills->where('deleted_at', null)
            ->select()->selectRaw('count(Id) as total')
            ->selectRaw('sum(PriceOut) as totalPriceOut')
            ->groupBy('So_Hoadon')->orderBy('Date_Create', 'ASC')->get();
        $priceDebt = 0;
        foreach ($bills as $value) {
            $priceDebt += ($value->PriceIn - $value->totalPriceOut);
            $value->setAttribute('totalPriceDebt', $priceDebt);
        }
        $sumDebt = 0;
        foreach ($bills as $value) {
            $sumDebt += $value->PriceIn - $value->totalPriceOut;
        }
        $bills = $bills->sortByDESC('Date_Create')->paginate(10);
        return ['bills' => $bills, 'So_Hoadon' => $So_Hoadon, 'Uname' => $uname, 'Date_Create' => $Date_Create, 'sumDebt' => $sumDebt];
    }

    public function ExportALlBillByUname(Request $request)
    {
        $codeOrderByBill = Bill::select('Codeorder')->get()->toArray();
        $billcodes = DB::table('quanlythe')->where('Sohoadon', '!=', null)->select('Sohoadon')->distinct()->get()->toArray();
        foreach ($codeOrderByBill as  $value) {
            $priceOrder = DB::table('oder')->where('codeorder', $value)->first();
            Bill::where('Codeorder', $value)->update([
                'PriceOut' => $priceOrder->total
            ]);
        }

        foreach ($billcodes as $value) {
            $sumPriceIn = DB::table('quanlythe')->where('Sohoadon', $value->Sohoadon)->selectRaw('sum(price_in) as totalPriceIn')->first();
            Bill::where('So_Hoadon', $value->Sohoadon)->update([
                'PriceIn' => $sumPriceIn->totalPriceIn
            ]);
        }
        $So_Hoadon = $request->eSo_Hoadon;
        $Date_Create = $request->eDate_Create;
        $uname = Auth::user()->uname;

        $bills = Bill::with('Order')->whereHas('Order', function ($query) use ($uname) {
            return $query->where('uname', $uname);
        });

        if (!empty($So_Hoadon)) {
            $bills = $bills->where('So_Hoadon', 'like', '%' . $So_Hoadon);
        }

        if (!empty($Date_Create)) {
            $bills = $bills->whereDate('Date_Create', $Date_Create);
        }

        $bills = $bills->where('deleted_at', null)
            ->select()->selectRaw('count(Id) as total')
            ->selectRaw('sum(PriceOut) as totalPriceOut')
            ->groupBy('So_Hoadon')->orderBy('Date_Create', 'ASC')->get();
        $priceDebt = 0;
        foreach ($bills as $value) {
            $priceDebt += ($value->PriceIn - $value->totalPriceOut);
            $value->setAttribute('totalPriceDebt', $priceDebt);            # code...
        }
        $bills = $bills->sortByDESC('Date_Create')->paginate(10);
        return ['bills' => $bills, 'So_Hoadon' => $So_Hoadon, 'Uname' => $uname, 'Date_Create' => $Date_Create];
    }

    public function getBillById(Request $request, $billcode)
    {
        $startDate = Carbon::create(2020, 10, 1);
        $endDate = $request->endDate;
        $date = Carbon::parse($endDate);
        $endDate2 = $date->addDays(1)->toDateString();
        $nowDate = now()->addDays(-2)->toDateString();
        $uname = Auth::user()->uname;
        $bill = Bill::where('So_Hoadon', $billcode)->with('Order')->whereHas('Order', function ($query) use ($uname) {
            return $query->where('uname', $uname);
        })->where('deleted_at', null)->with('Order.Transport', 'Product.ProductStandard', 'listProduct.ProductStandard')->orderBy('Date_Create', 'DESC')->get();
        foreach ($bill as $value) {
            $value->setAttribute('date_payment', $value->Order->date_payment);
        }
        $nap = PaymentCustomer::query()->where('Sohoadon', $billcode)->where('uname', $uname)->get();
        $codeorders = Bill::where('So_Hoadon', $billcode)->where('uname', $uname)->where('deleted_at', null)->get('Codeorder')->toArray();
        $mua = Order::query()->whereIn('codeorder', $codeorders)->with('listProduct')->get();
        $customer = collect($nap)->merge($mua)->sortBy('dateget');
        $deDebt = 0;
        $moneyNeedToPay = 0;
        $totalWeightReal = 0;
        $totalWeightKhoi = 0;
        $listRefund = refundCustomerModel::where('billcode', $billcode)->where('uname', $bill->first()->uname)->orderBy('date_in', 'DESC')->get();
        $moneyRefund = $listRefund->sum('money');
        ////
        $hien_mau = PaymentCustomer::query()->where('Sohoadon', $billcode)->where('uname', $uname)->orderBy('dateget', 'ASC')->get();
        $priceIn = 0;
        foreach ($hien_mau as $value) {
            $value->setAttribute('priceIn', $priceIn += $value->price_in);
        }
        ////
        $money = 0;
        foreach ($bill as $item) {
            $listProduct  = Product::where('codeorder', $item->Codeorder)->get();
            foreach ($listProduct as $item) {
                $money +=  $item->total;
            }
        }
        if ($startDate && $endDate) {
            $customer = $customer->whereBetween('date_payment', [$startDate, $endDate2]);
            $checkScroll = 1;
            $money = 0;
            foreach ($customer as $value) {
                if ($value->depositID) {
                    $deDebt += $value->price_in;
                } else {
                    if ($value->date_payment < $endDate2) {
                        foreach ($value->listProduct as $item) {
                            $deDebt -= $value->total;
                            $money += $item->total;
                        }
                    }
                }
                $value->setAttribute('deDebt', $deDebt);
            }
        } else {
            $checkScroll = 0;
            $mua = $mua->sortBy('dateget');
            // dd($mua);
            foreach ($mua as $value) {
                if ($value->date_payment < $nowDate) {
                    $moneyNeedToPay -= $value->total;
                }
            }
            foreach ($customer as $value) {
                if ($value->depositID) {
                    $deDebt += $value->price_in;
                } else {
                    $deDebt -= $value->total;
                }
                $value->setAttribute('deDebt', $deDebt);
            }
        }
        foreach ($bill as $value) {
            $weightKhoi = $value->Product->ProductStandard->length * $value->Product->ProductStandard->width * $value->Product->ProductStandard->height / 1000000;
            $value->setAttribute('totalWeightkhoi', $weightKhoi);
            $totalWeightKhoi += $weightKhoi * $value->Product->quantity;
            $totalWeightReal += $value->Product->ProductStandard->weight * $value->Product->quantity;
        }
        // foreach ($customer as $value) {
        //     if ($value->depositID) {
        //         $deDebt += $value->price_in;
        //     } else {
        //         $deDebt -= $value->total;
        //     }
        //     $value->setAttribute('deDebt', $deDebt);
        // }


        $hien_mau = $hien_mau->sortByDesc('dateget');
        $customer = $customer->sortByDesc('dateget');
        // return ['bill' => $bill, 'customer' => $customer, 'hien_mau' => $hien_mau];
        if (count($customer) >= 1) {
            $priceDebt = $customer->first()->deDebt;
        } else {
            $priceDebt = 0;
        }
        if ($request->check == 'true') {
            $hien_mau = $hien_mau;
            return $this->orderExportExcel->ExportOrder($bill, $hien_mau);
        } else {
            $hien_mau = $hien_mau->groupBy('dateget')->paginate(10);
            $bill = $bill->sortByDesc('date_payment');
            return [
                'bill' => $bill, 'priceDebt' => $priceDebt, 'hien_mau' => $hien_mau, 'startDate' => $startDate, 'endDate' => $endDate, 'checkScroll' => $checkScroll,
                'moneyNeedToPay' => $money, 'totalWeightReal' => $totalWeightReal, 'totalWeightKhoi' => $totalWeightKhoi,
                'customer' => $customer, 'priceIn' => $priceIn, 'moneyRefund' => $moneyRefund, 'listRefund' => $listRefund
            ];
        }
    }

    public function getPaymentByBillCodeAndDate(Request $request, $billcode)
    {
        $nap = PaymentCustomer::where('Sohoadon', $billcode)->where('uname', Auth::user()->uname)->whereDate('dateget', $request->date)->get();
        return view('orders.includes.modalPaymentDetail', compact('nap'));
    }

    public function getBillDetailById($codeorder)
    {
        $uname = Auth::user()->uname;
        $detail = Order::where('codeorder', $codeorder)->where('uname', $uname)
            ->with('Transport', 'Product.ProductStandard')->first();
        return ['detail' => $detail];
    }

    public function loadLog($codeorder)
    {
        $log = Order::where('codeorder', $codeorder)
            ->with('Transport', 'Product.ProductStandard', 'LogAdmin', 'LogUser')->first();
        $log = $log->LogAdmin->merge($log->LogUser)->sortBy('date');
        $html = view('orders.includes.logOrderDetail', compact('log'));
        return $html;
    }

    public function addLog(Request $request, $codeorder)
    {
        $uname = Auth::user()->uname;
        LogAdmin::create([
            'codeorder' => $codeorder,
            'uname' => $uname,
            'note' => $request->note
        ]);
    }
}
