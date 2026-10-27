
<?php

session_start();

if (!isset($_SESSION["id_cliente"])) {
    header("Location: ../login.php");
    exit;
}

if (
    !isset($_SESSION["ultima_atividade"]) ||
    (time() - $_SESSION["ultima_atividade"]) > 1800
) {
    $_SESSION = [];
    session_destroy();

    header("Location: ../login.php?erro=1");
    exit;
}

$_SESSION["ultima_atividade"] = time();

require_once "../conexao.php";

$erro = "";
$nome = "";
$cpf = "";
$telefone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $cpf = trim($_POST["cpf"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $senhaDigitada = $_POST["senha"] ?? "";

    if (
        $nome === "" ||
        !preg_match("/^[0-9]{11}$/", $cpf) ||
        $senhaDigitada === ""
    ) {
        $erro = "Preencha os campos corretamente. O CPF deve conter 11 números.";
    } else {

        // Impede o cadastro de outro usuário com o CPF administrativo.
        if ($cpf === "99988877722") {
            $erro = "Este CPF é reservado ao usuário administrativo.";
        } else {

            $sql = "INSERT INTO cliente (nome, cpf, telefone, senha)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $hashSenha = password_hash(
                    $senhaDigitada,
                    PASSWORD_DEFAULT
                );

                $stmt->bind_param(
                    "ssss",
                    $nome,
                    $cpf,
                    $telefone,
                    $hashSenha
                );

                try {

                    if ($stmt->execute()) {
                        $stmt->close();
                        $conn->close();

                        header("Location: cliente.php?msg=criado");
                        exit;
                    }

                    $erro = "Não foi possível cadastrar o cliente.";

                } catch (mysqli_sql_exception $e) {

                    if ($e->getCode() === 1062) {
                        $erro = "Este CPF já está cadastrado.";
                    } else {
                        error_log($e->getMessage());
                        $erro = "Ocorreu um erro ao cadastrar o cliente.";
                    }
                }

                $stmt->close();

            } else {
                $erro = "Não foi possível preparar o cadastro.";
            }
        }
    }
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Novo cliente - SAEP</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f4f7;
            color: #333;
        }

        nav {
            padding: 15px 30px;
            background: #2563eb;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            max-width: 500px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 8px;
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

        button, .voltar {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .erro {
            color: #dc2626;
        }
    </style>
</head>

<body>

    <nav>
        <a href="cliente.php">← Voltar para clientes</a>
    </nav>

    <div class="container">

        <h1>Novo cliente</h1>

        <?php if ($erro !== ""): ?>
            <p class="erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form action="add.php" method="POST">

            <label for="nome">Nome</label>
            <input
                type="text"
                id="nome"
                name="nome"
                maxlength="150"
                value="<?= htmlspecialchars($nome, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="cpf">CPF (somente números)</label>
            <input
                type="text"
                id="cpf"
                name="cpf"
                inputmode="numeric"
                pattern="[0-9]{11}"
                maxlength="11"
                value="<?= htmlspecialchars($cpf, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="telefone">Telefone</label>
            <input
                type="text"
                id="telefone"
                name="telefone"
                maxlength="20"
                value="<?= htmlspecialchars($telefone, ENT_QUOTES, "UTF-8") ?>"
            >

            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                autocomplete="new-password"
                required
            >

            <button type="submit">Cadastrar cliente</button>

            <a href="cliente.php" class="voltar">Cancelar</a>

        </form>

    </div>

</body>
</html>
