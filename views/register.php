<h2>НОВИЙ КЛІЄНТ</h2>
<?php if(isset($error)) echo "<div class='alert'>$error</div>"; ?>

<form action="/register" method="POST">
    <input type="text" name="username" placeholder="ПРИДУМАЙТЕ ЛОГІН" required>
    <input type="password" name="password" placeholder="ПРИДУМАЙТЕ ПАРОЛЬ" required>
    <button type="submit">СТВОРИТИ АКАУНТ</button>
</form>