<?php
require __DIR__.'/../app/db.php';
require __DIR__.'/../app/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=?");
  $stmt->execute([$username]);
  $admin = $stmt->fetch();

  if ($admin && password_verify($password, $admin['password_hash'])) {
    login_admin($admin);
    header('Location: /admin/dashboard.php');
    exit;
  } else {
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
  }
  
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title>
<style>
body {
  margin: 0;
  font-family: Arial, sans-serif;
  background: linear-gradient(135deg, #144623ff, #2a7c76);
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}
.login-box {
  background: rgba(255,255,255,0.1);
  padding: 40px;
  border-radius: 10px;
  width: 360px;
  text-align: center;
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}
.logo-box {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}
.logo-box img {
  height: 60px;
}
.icon-top {
  font-size: 48px;
  color: white;
  margin-bottom: 20px;
}
.input-group {
  display: flex;
  align-items: center;
  background: white;
  border-radius: 6px;
  margin-bottom: 15px;
  padding: 0 10px;
}
.input-group input {
  border: none;
  outline: none;
  flex: 1;
  padding: 10px;
  font-size: 14px;
}
.input-group i {
  color: #666;
  cursor: pointer;
}
.options {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: white;
  margin-bottom: 20px;
}
.options a {
  color: white;
  text-decoration: none;
}
.login-btn {
  width: 100%;
  background: #2a7c76;
  color: white;
  border: none;
  padding: 12px;
  font-size: 16px;
  border-radius: 6px;
  cursor: pointer;
}
.login-btn:hover {
  background: #256964;
}
.alert-err {
  background: #ffe9ea;
  color: #b62433;
  padding: 8px 10px;
  border-radius: 6px;
  margin-bottom: 15px;
  font-size: 14px;
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="login-box">
    <div class="logo-box">
      <img src="/public/assets/picture/A.png" alt="โลโก้ 1" onerror="this.style.display='none'">
      <img src="/public/assets/picture/MOPH202503.png" alt="โลโก้ 2" onerror="this.style.display='none'">
    </div>
    <div class="icon-top"><i class="fas fa-users"></i></div>

    <?php if (!empty($error)): ?>
      <div class="alert-err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="username" placeholder="Username" required>
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" id="password" name="password" placeholder="Password" required>
        <i class="fas fa-eye" id="togglePassword"></i>
      </div>
      <div class="options">
        <label><input type="checkbox" name="remember"> Remember me</label>
        <a href="#">Forgot Password?</a> 
      </div>
      <!-- ปุ่ม Login สีเขียว มีแบบ Hover และแบบธรรมดา ค่อยคิด55555 -->   
       
<!-- <button
  type="submit"
  class="relative flex items-center px-6 py-3 overflow-hidden font-medium transition-all bg-green-500 rounded-md group"
>
  <span
    class="absolute top-0 right-0 inline-block w-4 h-4 transition-all duration-500 ease-in-out bg-green-700 rounded group-hover:-mr-4 group-hover:-mt-4"
  >
    <span
      class="absolute top-0 right-0 w-5 h-5 rotate-45 translate-x-1/2 -translate-y-1/2 bg-white"
    ></span>
  </span>
  <span
    class="absolute bottom-0 rotate-180 left-0 inline-block w-4 h-4 transition-all duration-500 ease-in-out bg-green-700 rounded group-hover:-ml-4 group-hover:-mb-4"
  >
    <span
      class="absolute top-0 right-0 w-5 h-5 rotate-45 translate-x-1/2 -translate-y-1/2 bg-white"
    ></span>
  </span>
  <span
    class="absolute bottom-0 left-0 w-full h-full transition-all duration-500 ease-in-out delay-200 -translate-x-full bg-green-600 rounded-md group-hover:translate-x-0"
  ></span>
  <span
    class="relative w-full text-left text-white transition-colors duration-200 ease-in-out group-hover:text-white"
  >
    LOGIN
  </span> -->
  <button 
  type="submit"
  class="bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-6 rounded-lg shadow-md transition-transform duration-300 hover:scale-105"
>LOGIN 
</button>


    </form>
  </div>
<script src="https://cdn.tailwindcss.com"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
  const passwordField = document.getElementById('password');
  const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
  passwordField.setAttribute('type', type);
  this.classList.toggle('fa-eye-slash');
});
</script>
</body>
</html>
