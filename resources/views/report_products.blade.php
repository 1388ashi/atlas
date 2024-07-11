<!doctype html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>گزارش محصولات</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

    <style>
        body{
            direction: rtl;
        }
        th,td {
            padding: 2px 4px !important;
            text-align: center;
        }
        svg{
            width: 25px;
            transform: rotate(180deg);
        }
        /*#navigation>nav>div:first-child {*/
        /*    display: none;*/
        /*}*/
    </style>
</head>
<body dir="rtl">

<div class="container">

    <form>
        <div class="form-group row">
            <label class="label col-2" for="limit">تعداد در هر صفحه</label> <input class="form-control col-1" type="text" disabled value="{{$limit}}">
{{--            <select name="limit" id="limit" class="form-control col-1">--}}
{{--                <option value="20" @if($limit == 20) selected @endif>20</option>--}}
{{--                <option value="20" @if($limit == 100) selected @endif>100</option>--}}
{{--                <option value="20" @if($limit == 1000) selected @endif>1000</option>--}}
{{--                <option value="20" @if($limit == 5000) selected @endif>5000</option>--}}
{{--            </select>--}}
{{--            <button class="btn btn-success" type="submit">اعمال</button>--}}
        </div>
    </form>

    <hr>

    <table class="table table-striped table-hover table-bordered">
        <thead class="thead-light">
        <tr>
            <th> ردیف </th>
            <th> شناسه محصول </th>
            <th> عنوان محصول </th>
            <th> موجودی </th>
            <th> وضعیت </th>
            <th> قیمت واحد </th>
            <th> قیمت خرید </th>
        </tr>
        </thead>
        <tbody>
        @php
            $num = ($data->currentPage()-1)*$limit
        @endphp
        @foreach ($data as $index => $item)
            <?php
                $status = match ($item->status){
                    'draft' => 'پیش نویس',
                    'available' => 'موجود',
                    'soon' => 'به زودی',
                    'out_of_stock' => 'ناموجود',
                    'available_offline' => 'موجود آفلاین',
                    default => 'نامشخص'
                }
                ?>
        <tr>
            <td>{{ $index + $num +1 }}</td>
            <td>{{ $item->p_id }}</td>
            <td>{{ $item->p_title }}</td>
            <td>{{ $item->sum }}</td>
            <td>{{ $status }}</td>
            <td>{{ number_format( $item->unit_price, 0 ) }}</td>
            <td>{{ number_format( $item->purchase_price, 0 ) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

    <div id="navigation">
        {!! $data->appends(['limit' => $limit])->links() !!}
    </div>


</div>

{{--{!! $data->appends(['sort' => 'votes'])->links() !!}--}}

</body>
</html>
