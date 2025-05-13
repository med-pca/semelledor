<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
  <h3>Login</h3>
  <form method='POST' action='login_check.php'>
    <input type='text' name='username' class='form-control mb-2' placeholder='Username' required>
    <input type='password' name='password' class='form-control mb-2' placeholder='Password' required>
    <button type='submit' class='btn btn-primary'>Login</button>
  </form>
</body>
</html>