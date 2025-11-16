<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Foodies</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Foodies</a>
  </div>
</nav>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="mb-3">Login</h4>
          <div id="login-msg"></div>
          <form id="login-form" autocomplete="off">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input name="password" type="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Login</button>
          </form>
          <div class="text-center mt-3">
            <a href="signup.php">Don't have an account? Sign up</a> | <a href="index.php">Back to menu</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function showMsg(text,type='info'){const box=document.getElementById('login-msg');box.innerHTML='<div class="alert alert-'+(type==='error'?'danger':type)+'">'+text+'</div>'; }
document.getElementById('login-form').addEventListener('submit', e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = {
    action: 'login',
    email: fd.get('email').trim(),
    password: fd.get('password')
  };
  fetch('php/users.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  })
  .then(r => r.json().catch(()=>({success:false,message:'Bad JSON'})))
  .then(d => {
    if (d.success) {
      localStorage.setItem('currentUser', JSON.stringify({ id: d.user_id, name: d.name, email: d.email }));
      showMsg('Login successful. Redirecting...','success');
      setTimeout(()=> location.href='index.php', 800);
    } else {
      showMsg(d.message || 'Login failed','error');
    }
  })
  .catch(()=> showMsg('Network error','error'));
});
</script>
</body>
</html>
