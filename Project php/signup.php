<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up - Foodies</title>
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
          <h4 class="mb-3">Create Account</h4>
          <div id="signup-msg"></div>
          <form id="signup-form" autocomplete="off">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input name="name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input name="password" type="password" class="form-control" minlength="6" required>
            </div>
            <button class="btn btn-success w-100" type="submit">Sign Up</button>
          </form>
          <div class="text-center mt-3">
            <a href="login.php">Already have an account? Login</a> | <a href="index.php">Back to menu</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function showMsg(text,type='info'){const box=document.getElementById('signup-msg');box.innerHTML='<div class="alert alert-'+(type==='error'?'danger':type)+'">'+text+'</div>'; }
document.getElementById('signup-form').addEventListener('submit', e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = {
    name: fd.get('name').trim(),
    email: fd.get('email').trim(),
    password: fd.get('password')
  };
  fetch('php/users.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  })
  .then(r => {
    return r.text(); // get raw first
  })
  .then(text => {
    console.log('Raw signup response:', text); // debug output
    try {
      const d = JSON.parse(text);
      if (d.success) {
        localStorage.setItem('currentUser', JSON.stringify({ id: d.user_id, name: payload.name, email: payload.email }));
        showMsg('Signup successful. Redirecting...','success');
        setTimeout(()=> location.href='index.php', 800);
      } else {
        showMsg(d.message || 'Signup failed','error');
      }
    } catch(e) {
      showMsg('Server returned invalid response. Check console.','error');
      console.error('Parse error:', e, 'Response:', text);
    }
  })
  .catch(err => {
    console.error('Network error:', err);
    showMsg('Network error','error');
  });
});
</script>
</body>
</html>
