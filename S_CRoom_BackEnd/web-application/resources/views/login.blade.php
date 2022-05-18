<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Log in </title>
</head>
<body>
<form action="/login" method="post" >
    @csrf
    <input type="text" name="student_id">
    <input type="password" name="password">
    @error('student_id')
    <span>{{ $message }}</span>
    @enderror
    <input type="submit">
</form>
</body>
</html>
