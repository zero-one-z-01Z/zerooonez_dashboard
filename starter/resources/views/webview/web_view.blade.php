<!DOCTYPE html>
<html lang="{{App::getLocale()}}" dir="{{ App::getLocale() == 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{env('APP_NAME')}}</title>
</head>
<body>
<div style="background-color: #8C73CD; padding: 20px 0; width: 100%; text-align: center">
    <span style="color: #ffffff; align-content: center">{{env('APP_NAME')}}</span></div>
<h1 style="font-weight: bold; text-align: center;">{{$data['title']}}</h1>
<div>
    {!! $data['body'] !!}
</div>
</body>
</html>
