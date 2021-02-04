<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\addUnamesRequest;
use App\Http\Requests\Orders\PaymentCustomerRequest;
use App\Services\Orders\PaymentCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentCustomer;

class PaymentCustomerController extends Controller
{
    protected $paymentCustomer;

    public function __construct(PaymentCustomerService $paymentCustomer)
    {
        $this->paymentCustomer = $paymentCustomer;
    }

    public function index(Request $request)
    {
        $data = $this->paymentCustomer->indexAll($request);
        return view('orders.payment_customers', compact('data'));
    }

    public function insert(PaymentCustomerRequest $request)
    {
        return $this->paymentCustomer->insert($request);
    }

    public function update(Request $request, $id)
    {
        return $this->paymentCustomer->updateById($request, $id);
    }
    public function addUnames(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "deposit" => "unique:quanlythe,depositID",
            "getDate" => "required|date",
            "uname.*" => "exists:users,uname",
            "hoadon.*" => "required"
        ]);

        if ($validator->fails()) {
            $er = $validator->errors();
            return response()->json([$er]);
        }
        for ($i = 0; $i < count($request->uname); $i++) {
            $create = PaymentCustomer::create([
                'uname' =>   $request->uname[$i],
                'depositID' => $request->deposit,
                'dateget' => $request->getDate,
                'note' => $request->note[$i],
                'price_in' => $request->price[$i],
                'Sohoadon' => $request->hoadon[$i]
            ]);
        }
        if ($create) {
            return "oke";
        } else {
            return "false";
        }
    }
}
