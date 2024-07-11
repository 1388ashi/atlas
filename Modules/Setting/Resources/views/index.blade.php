@extends('setting::layouts.master')

@section('content')
    @if(request('find'))
        <div class="alert alert-info m-md-auto text-md-right w-50" style="margin-bottom: 1% !important;">
            <span>
            متن جستجوی شما :
            {{ request('find') }}
            </span>
            <span style="float: left;font-size: smaller;">
            تعداد رکورد یافت شده :
            {{ count($settings) }}
                |
                <a style="font-size: x-small" href="{{route('settings.index')}}">نمایش همه</a>
            </span>
        </div>
    @endif
    <span style="float: right">تعداد تنظیمات  ({{ count($settings) }})</span>
    <table  class="table table-hover w-auto m-auto mt-2 " style="2px solid powderblue">
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">label</th>
            <th scope="col">name</th>
            <th scope="col">value</th>
            <th scope="col">options</th>
            <th scope="col">group</th>
            <th scope="col">type</th>
            <th scope="col">private</th>
            <th scope="col">created_at</th>
            <th scope="col">actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($settings as $setting)
            <tr>
                <th scope="row">{{$setting->id}}</th>
                <td>{{$setting->label}}</td>
                <td>{{$setting->name}}</td>
                <td>{{$setting->value}}</td>
                <td>{{$setting->options ?? 'null'}}</td>
                <td>{{$setting->group}}</td>
                <td>{{$setting->type}}</td>
                <td>{{ $setting->private == true  ? "True" : "False" }} </td>
                <td>{{$setting->created_at}}</td>
                <td class="row justify-content-between">
                    <a href="{{ route('settings.edit' , $setting) }}" class="btn btn-success">
                        <i class="fas fa-edit"></i>
                    </a>

                    <form action="{{ route('settings.destroy' , $setting) }}" method="POST">
                        @csrf @method('delete')
                        <button class="btn btn-danger" type="submit"><i class="far fa-trash-alt"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
