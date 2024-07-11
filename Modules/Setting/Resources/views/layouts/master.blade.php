<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css">
        <title>Module Setting</title>
    </head>
    <body dir="rtl">
            <nav class="navbar navbar-light bg-light card-header mb-5">
                <div class="container-fluid">
                    <a class="navbar-brand" href="{{ route('settings.index') }}">تنظیمات</a>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{route('settings.create')}}">ایجاد تنظیمات</a>
                        </li>
                    </ul>
                    <form class="d-flex">
                        <input class="form-control me-2" type="search" name="find" placeholder="Enter name" aria-label="Search">
                        <button class="btn btn-outline-success mr-1" type="submit">جستجوی</button>
                    </form>
                </div>
            </nav>

            @if($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>Hi Developer!</strong><br>
                         {{ $error }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endforeach
            @endif


        @yield('content')

            @yield('script')
    </body>
</html>
