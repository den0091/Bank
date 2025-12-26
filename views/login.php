<h2>АВТОРИЗАЦІЯ</h2>
<?php if(isset($error)) echo "<div class='alert'>$error</div>"; ?>

<form action="/login" method="POST">
    <input type="text" name="username" placeholder="ЛОГІН" required>
    <input type="password" name="password" placeholder="ПАРОЛЬ" required>
    <button type="submit">УВІЙТИ В СИСТЕМУ</button>
</form>
<a href="/register">Немає акаунту? Реєстрація</a>