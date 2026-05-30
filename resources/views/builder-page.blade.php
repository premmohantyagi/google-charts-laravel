<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Google Charts Builder</title>
</head>
<body>
    @include('google-charts::builder', ['chartTypes' => $chartTypes])
</body>
</html>
