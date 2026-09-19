@extends('zerooonez-dashboard::layout.auth-layout')

@section('title', 'Forget Password')

@section('content')

    @include('zerooonez-dashboard::screen.auth.form', [
        'action' => route('admin.send_forget_password'),
        'field' => __('inputs.email'),
        'showPassword' => false,

    ])

@endsection
