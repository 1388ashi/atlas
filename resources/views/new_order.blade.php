<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <title>سفارش جدید</title>
</head>
<body>
<h2>سفارش جدید ثبت شد</h2>
<br>
<h3>مشخصات :</h3>

<table>
    <tr>
        <td><strong>شناسه سفارش :</strong></td>
        <td>{{ $order->id }}</td>
    </tr>
    <tr>
        <td><strong>نام مشتری:</strong></td>
        <td>{{ json_decode($order->address)->first_name.' '.json_decode($order->address)->last_name }}</td>
    </tr>
    <tr>
        <td><strong>زمان ثبت :</strong></td>
        <td>{{ verta($order->created_at)->format('(H:i:s) Y-m-d') }}</td>
    </tr>
    <tr>
        <td><strong>آدرس : </strong></td>
        <td>{{json_decode($order->address)->address}}</td>
    </tr>
    <tr>
        <td><strong>شماره موبایل :</strong></td>
        <td>{{ $order->customer->mobile }}</td>
    </tr>
    <tr>
        <td><strong>مبلغ کل :</strong></td>
        <td>{{ $order->total_amount }}</td>

    </tr>
</table>
<hr>
<h3>اقلام :</h3>
<table border="1">
    @foreach($order->items as $item)
        {{$attr = ''}}
        @foreach($item->variety->attributes as $x)
            @php $attr = $attr.' '.$x->label.' '.$x->pivot->value @endphp
        @endforeach
        <tr>
            <td>
                <span style="font-size:18px;margin:10px;line-height: 2">
                    {{$item->product->title}}
                    <br>
                    {{$attr}}
                    <br>
                     تعداد: {{' '.$item->quantity}}
                </span>
            </td>
        </tr>
    @endforeach
</table>
</body>
</html>
