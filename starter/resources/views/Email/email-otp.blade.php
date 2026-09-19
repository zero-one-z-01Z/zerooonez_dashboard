
    <!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{env('APP_NAME')}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        img {
            max-width: 100%;
            height: auto;
        }
        .message {
            margin-top: 20px;
            font-size: 16px;
            color: #333333;
        }
        .code {
            margin-top: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
<div class="container">
    <img src="{{url('logo.svg')}}" alt="Company Logo">
    <div class="message">
        <p>{{__('admin.dear_user')}}</p>
        <p>{{__('admin.one_time_password')}}</p>
        <p class="code">{{$data}}</p> <!-- Replace "YourGeneratedOTP123" with the actual generated OTP code -->
        <p>{{__('admin.use_this_code')}}</p>
    </div>
</div>
</body>
</html>
