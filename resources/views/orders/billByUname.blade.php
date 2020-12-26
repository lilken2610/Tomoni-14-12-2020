@extends('layout')
@section('title', 'Hóa đơn đặt hàng')
@section('content')
<div class="main-panel">
    <div class="content content-documentation">
        <div class="container-fluid">
            <div class="card card-documentation">
                <div class="card-header bg-info-gradient text-white bubble-shadow">
                    <h4>Hoá đơn của {{$data['Uname']}}</h4>
                </div>
                <div class="card-body row">
                    <div class="card" style=" margin-left:1%; width:100%; padding:1%">
                        <div >
                            <form action="{{route('orders.bills.indexAllByUname', $data['Uname'])}}">
                                <fieldset >
                                    <div class="form-row" style=" margin-top: 1%;">
                                        <div >
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                style="margin-left: 2%;"
                                                >Tìm kiếm</button>
                                        </div>
                                        <input
                                            type="text"
                                            class="form-control ml-2"
                                            value="{{$data['So_Hoadon']}}"
                                            name="So_Hoadon"
                                            placeholder="Nhập So hoa don"
                                            style="width: 11%;"/>
                                        <div >
                                            <input class="form-control" type="date" value="{{$data['Date_Create']}}" name="Date_Create">
                                        </div>
                                        <input
                                            type="text"
                                            class="form-control ml-2"
                                            id="priceIn"
                                            placeholder="Nhập price in"
                                            style="width: 11%;"/>
                                        <input
                                            type="text"
                                            class="form-control ml-2"
                                            id="priceOut"
                                            placeholder="Nhập price out"
                                            style="width: 11%;"/>
                                    </fieldset>
                                </form>

                                <div style="float: right" class="mt-3">
                                    {!! $data['bills']->withQueryString()->links('commons.paginate') !!}</div>
                                <table class="table table-bordered table-striped" style="margin-top: 1%;">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>So hoa don</th>
                                            <th>Uname</th>
                                            <th>Price In</th>
                                            <th>Price Out</th>
                                            <th>Total</th>
                                            <th>PriceBelb</th>
                                            <th>Date Create</th>
                                        </tr>
                                    </thead>
                                    <tbody id="myTable">
                                        @foreach ($data['bills']->unique('So_Hoadon') as $item)
                                        <tr>
                                            <td>{{$item->Id}}</td>
                                            <td>
                                                <a href="{{route('orders.bills.getBillById', $item->So_Hoadon)}}">{{$item->So_Hoadon}}</a>
                                            </td>
                                            <td>{{$item['Order']->uname}}</td>
                                            <td>{{number_format($item->PriceIn, 0)}}</td>
                                            <td>
                                                {{number_format($item->totalPriceOut, 0)}}
                                            </td>
                                            <td>{{$item->total}}</td>
                                            <td>{{$item->total}}</td>
                                            <td>{{Carbon\Carbon::parse($item->Date_Create)->format('d/m/Y')}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
<script>
    function searchCodeOrder(obj) {
        var text = $(obj).val();
            if(text.length >3){
                $.ajax({
                type: 'GET',
                url: "{{route('commons.searchCodeOrder')}}",
                data: {
                    search_ordercode: text
                },
                success: function (response) {
                    var len = response.length;
                    $("#listcodeorder").empty();
                    for (var i = 0; i < len; i++) {
                        var name = response[i]['codeorder'];
                        var name1 = response[i]['quantity'];
                        var name2 = response[i]['total'];

                        $("#listcodeorder").append("<option value='" + name + "'>" + "Quantity: " + name1 + " Total: " + name2 +
                            "</option>");

                    }
                }
            });
            };
        }

        function searchBillCode(obj) {
            // debugger;
            var text = $(obj).val();
                if(text.length >3){
                    $.ajax({
				type: 'GET',
				url: "{{route('commons.searchBillCode')}}",
				data: {
                    BillCode: text
				},
				success: function(response) {
                    var len = response.length;
                    $("#listbillcode").empty();
                for( var i = 0; i<len; i++){
                    var name = response[i]['depositID'];
                    var name1 = response[i]['uname'];
                    var name2 = response[i]['date_inprice'];
                    var name3 = response[i]['date_insert'];

                    $("#listbillcode").append("<option value='"+name+"'>"+'Uname: '+ name1 + ' Ngày vào: '+ name2 + ' Ngày vô tiền: '+ name3 +"</option>");

                }
				}
			    });
                }
        }
</script>
@endsection