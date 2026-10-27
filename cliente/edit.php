
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

$cpfAdmin = "99988877722";
$erro = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
}

if (!$id || $id <= 0) {
    $conn->close();
    header("Location: cliente.php?msg=erro");
    exit;
}

// O administrador não pode ser editado por este módulo.
$sql = "SELECT id, nome, cpf, telefone
        FROM cliente
        WHERE id = ? AND cpf <> ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id, $cpfAdmin);
$stmt->execute();

$resultado = $stmt->get_result();
$cliente = $resultado->fetch_assoc();

$stmt->close();

if (!$cliente) {
    $conn->close();
    header("Location: cliente.php?msg=erro");
    exit;
}

$nome = $cliente["nome"];
$cpf = $cliente["cpf"];
$telefone = $cliente["telefone"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $cpf = trim($_POST["cpf"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $novaSenha = $_POST["senha"] ?? "";

    if (
        $nome === "" ||
        !preg_match("/^[0-9]{11}$/", $cpf)
    ) {
        $erro = "Preencha o nome e informe um CPF com 11 números.";
    } elseif ($cpf === $cpfAdmin) {
        $erro = "Este CPF é reservado ao usuário administrativo.";
    } else {

        try {

            if ($novaSenha !== "") {

                $hashSenha = password_hash(
                    $novaSenha,
                    PASSWORD_DEFAULT
                );

                $sql = "UPDATE cliente
                        SET nome = ?, cpf = ?, telefone = ?, senha = ?
                        WHERE id = ? AND cpf <> ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssis",
                    $nome,
                    $cpf,
                    $telefone,
                    $hashSenha,
                    $id,
                    $cpfAdmin
                );

            } else {

                $sql = "UPDATE cliente
                        SET nome = ?, cpf = ?, telefone = ?
                        WHERE id = ? AND cpf <> ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssis",
                    $nome,
                    $cpf,
                    $telefone,
                    $id,
                    $cpfAdmin
                );
            }

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();

                header("Location: cliente.php?msg=editado");
                exit;
            }

            $stmt->close();
            $erro = "Não foi possível atualizar o cliente.";

        } catch (mysqli_sql_exception $e) {

            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }

            if ($e->getCode() === 1062) {
                $erro = "Este CPF já está cadastrado.";
            } else {
                error_log($e->getMessage());
                $erro = "Ocorreu um erro ao atualizar o cliente.";
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

    <title>Editar cliente - SAEP</title>

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

        .ajuda {
            color: #666;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <nav>
        <a href="cliente.php">← Voltar para clientes</a>
    </nav>

    <div class="container">

        <h1>Editar cliente</h1>

        <?php if ($erro !== ""): ?>
            <p class="erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form action="edit.php" method="POST">

            <input
                type="hidden"
                name="id"
                value="<?= (int) $id ?>"
            >

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

            <label for="senha">Nova senha (opcional)</label>
            <input
                type="password"
                id="senha"
                name="senha"
                autocomplete="new-password"
            >

            <p class="ajuda">
                Deixe em branco para manter a senha atual.
            </p>

            <button type="submit">Salvar alterações</button>

            <a href="cliente.php" class="voltar">Cancelar</a>

        </form>

    </div>

</body>
</html>
