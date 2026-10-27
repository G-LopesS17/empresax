
<?php

session_start();

// Se já estiver autenticado, direciona para a página inicial.
if (isset($_SESSION["id_cliente"])) {
    header("Location: index.php");
    exit;
}

$mensagem = "";

if (isset($_GET["erro"])) {
    $mensagem = "CPF ou senha inválidos. Verifique seus dados.";
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SAEP</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f2f4f7;
            color: #333;
        }

        .container {
            width: 350px;
            max-width: calc(100% - 32px);
            margin: 100px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-top: 0;
            text-align: center;
            font-size: 25px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 11px;
            border: none;
            border-radius: 4px;
            background-color: #2563eb;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #1d4ed8;
        }

        .erro {
            color: #dc2626;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>SAEP - Login</h1>

        <?php if ($mensagem !== ""): ?>
            <p class="erro">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form action="autenticar.php" method="POST">

            <label for="cpf">CPF (somente números)</label>
            <input
                type="text"
                id="cpf"
                name="cpf"
                inputmode="numeric"
                maxlength="11"
                pattern="[0-9]{11}"
                placeholder="Digite seu CPF"
                autocomplete="username"
                required
            >

            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                placeholder="Digite sua senha"
                autocomplete="current-password"
                required
            >

            <button type="submit">Entrar</button>

        </form>

    </div>

</body>
</html>
