@extends('zerooonez-dashboard::layout.auth-layout')

@section('title', 'Login')

@section('content')

    @include('zerooonez-dashboard::screen.auth.form', [
        'action' => route('admin.send_login'),
        'field' => __('inputs.email'),
        'showPassword' => true,
        'extra_button' => [
            'route'=>route('admin.forget_password'),
            'text'=>__('buttons.forget_password')
],
    ])

@endsection
