@extends('setting::layouts.master')

@section('content')

    <form method="POST" id="vue" action="{{route('settings.store')}}" dir="rtl" class="w-50 m-auto shadow-lg p-5 border mt-lg-3 rounded-lg">
        @csrf
        <div class="mb-3">
            <label for="exampleInputLabel" class="form-label">Label</label>
            <input type="text" name="label" class="form-control" id="exampleInputLabel" aria-describedby="emailHelp">
        </div>
        <div class="mb-3">
            <label for="exampleInputName" class="form-label">Name</label>
            <input type="text" name="name" class="form-control" id="exampleInputName">
        </div>
        <div class="mb-3">
            <label for="exampleInputValue" class="form-label">Value</label>
            <input type="text" name="value" class="form-control" id="exampleInputValue">
        </div>
        <div class="mb-3">
            <label for="exampleInputGroup" class="form-label">Group</label>
            <input type="text" name="group" class="form-control" id="exampleInputGroup">
        </div>
        <div class="mb-3">
            <label for="exampleInputType" class="form-label">Type</label>
            <input type="text" name="type" v-model="input" @keyup="onFocus" class="form-control" id="exampleInputType">
        </div>
        <div class="mb-3"  v-show="showMultiSelect">
            <label for="exampleInputType" class="form-label">Options</label>
            <input type="text" name="options" class="form-control" id="exampleInputType">
            <span dir="rtl">مقادیر را با "،" جدا کنید</span>
        </div>
        <div class="mb-3 form-check">
            <label class="form-check-label ml-2" for="examplePrivate">Check me to private</label>
            <input type="checkbox"  name="private" class="form-check-input" id="examplePrivate">
        </div>

        <button type="submit" class="btn btn-primary w-100">Submit</button>
    </form>


@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script>
        new Vue({
            el: '#vue',
            data: {
                input: null,
                showMultiSelect: false
            },
            methods: {
                onFocus() {
                    if (this.input === 'multi_select') {
                        this.showMultiSelect =true;
                    }else {
                        this.showMultiSelect =false;
                    }
                }
            }
        })
    </script>
@endsection
