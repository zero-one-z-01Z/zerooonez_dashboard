<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME') }}</title>
    <style>
        body {
            font-family: "Tajawal", Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            direction: rtl;
        }
        .container {
            max-width: 650px;
            margin: 40px auto;
            padding: 25px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        img {
            max-width: 180px;
            display: block;
            margin: 0 auto 20px auto;
        }
        .message {
            margin-top: 15px;
            font-size: 16px;
            color: #333;
            line-height: 1.8;
        }
        .section {
            margin-top: 25px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
        }
        .section h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }
        .info-item {
            margin: 6px 0;
        }
        .label {
            font-weight: bold;
            color: #444;
        }
        .code {
            margin-top: 25px;
            font-size: 22px;
            font-weight: bold;
            color: #007bff;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <img src="{{ asset('logo.png') }}" alt="Company Logo">

    <div class="message">
        <p>{{ __('admin.dear_user') }}</p>
        <p>{{ __('admin.have_new_notification') }}</p>
    </div>

    <!-- استلام وتسليم السيارة -->
    <div class="section">
        <h3>{{__('admin.handle_auction')}}</h3>

        <div class="info-item"><span class="label">{{__('admin.from_user')}}:</span> {{ $auction->user?->name }}</div>
        <div class="info-item"><span class="label">{{__('inputs.phone')}}:</span> {{ $auction->user?->phone }}</div>
        <div class="info-item"><span class="label">{{__('admin.area')}}:</span> {{ $auction->area?->city?->name }}</div>
        <div class="info-item"><span class="label">{{__('admin.user_identity')}}:</span> {{ $auction->user?->national_id }}</div>
        <hr style="margin:10px 0;">
        <div class="info-item"><span class="label">{{__('admin.to_user')}}:</span> {{ $auction->best_user?->name }}</div>
        <div class="info-item"><span class="label">{{__('inputs.phone')}}:</span> {{ $auction->best_user?->phone }}</div>
        <div class="info-item"><span class="label">{{__('admin.area')}}:</span> {{ $auction->best_user?->city?->name }}</div>
        <div class="info-item"><span class="label">{{__('admin.user_identity')}}:</span> {{ $auction->best_user?->national_id }}</div>
    </div>

    <!-- بيانات السيارة -->
    <div class="section">
        <h3>{{__('admin.car_info')}}</h3>

        <div class="info-item"><span class="label">{{__("inputs.brand_name")}}:</span> {{ $auction->brand_model?->brand?->name }}</div>
        <div class="info-item"><span class="label">{{__("inputs.brand_model")}}:</span> {{ $auction->brand_model?->name }}</div>
        <div class="info-item"><span class="label">{{__("inputs.brand_model_class_name")}}:</span> {{ $auction->brand_model_class?->name }}</div>
        <div class="info-item"><span class="label">{{__('inputs.car_serial')}}:</span> {{ $auction->car_serial }}</div>
    </div>

</div>
</body>
</html>
