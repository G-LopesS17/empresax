
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
$busca = trim($_GET["busca"] ?? "");
$clientes = [];

// Busca clientes pelo nome, excluindo a conta administrativa.
if ($busca !== "") {

    $termo = "%" . $busca . "%";

    $sql = "SELECT id, nome, cpf
            FROM cliente
            WHERE nome LIKE ?
              AND cpf <> '99988877722'
            ORDER BY nome ASC
            LIMIT 50";

    $stmtBusca = $conn->prepare($sql);
    $stmtBusca->bind_param("s", $termo);
    $stmtBusca->execute();

    $resultadoBusca = $stmtBusca->get_result();

    while ($cliente = $resultadoBusca->fetch_assoc()) {
        $clientes[] = $cliente;
    }

    $stmtBusca->close();
}

// Processa o cadastro.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $idCliente = filter_input(
        INPUT_POST,
        "id_cliente",
        FILTER_VALIDATE_INT
    );

    if ($nome === "" || strlen($nome) > 100) {
        $erro = "Informe um nome de equipamento válido.";
    } elseif (!$idCliente || $idCliente <= 0) {
        $erro = "Selecione um cliente válido.";
    } else {

        // Confirma que o cliente selecionado existe e não é o administrador.
        $sql = "SELECT id
                FROM cliente
                WHERE id = ?
                  AND cpf <> '99988877722'
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idCliente);
        $stmt->execute();

        $clienteValido = $stmt->get_result()->num_rows === 1;
        $stmt->close();

        if (!$clienteValido) {
            $erro = "O cliente selecionado não existe ou não pode ser utilizado.";
        } else {

            $sql = "INSERT INTO equipamento (nome, id_cliente)
                    VALUES (?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nome, $idCliente);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();

                header("Location: equipamento.php?msg=criado");
                exit;
            }

            error_log($stmt->error);
            $erro = "Não foi possível cadastrar o equipamento.";

            $stmt->close();
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

    <title>Novo equipamento - SAEP</title>

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
            max-width: 600px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 8px;
        }

        label {
            display: block;
            margin: 15px 0 5px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button, .voltar {
            display: inline-block;
            margin-top: 15px;
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

        .resultado {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <nav>
        <a href="equipamento.php">← Voltar para equipamentos</a>
    </nav>

    <div class="container">

        <h1>Novo equipamento</h1>

        <?php if ($erro !== ""): ?>
            <p class="erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <h3>1. Buscar cliente</h3>

        <form action="add.php" method="GET">

            <label for="busca">Nome do cliente</label>

            <input
                type="text"
                id="busca"
                name="busca"
                value="<?= htmlspecialchars($busca, ENT_QUOTES, "UTF-8") ?>"
                placeholder="Digite o nome ou parte do nome"
                required
            >

            <button type="submit">Buscar cliente</button>

        </form>

        <?php if ($busca !== ""): ?>

            <div class="resultado">

                <?php if (count($clientes) > 0): ?>

                    <form action="add.php" method="POST">

                        <label for="id_cliente">
                            2. Selecione o cliente
                        </label>

                        <select
                            id="id_cliente"
                            name="id_cliente"
                            required
                        >
                            <option value="">Selecione um cliente</option>

                            <?php foreach ($clientes as $cliente): ?>

                                <option value="<?= (int) $cliente["id"] ?>">
                                    <?= htmlspecialchars($cliente["nome"], ENT_QUOTES, "UTF-8") ?>
                                    (CPF: <?= htmlspecialchars($cliente["cpf"], ENT_QUOTES, "UTF-8") ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <label for="nome">Nome do equipamento</label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            maxlength="100"
                            value="<?= htmlspecialchars($nome, ENT_QUOTES, "UTF-8") ?>"
                            placeholder="Ex.: Notebook Dell"
                            required
                        >

                        <button type="submit">Cadastrar equipamento</button>

                    </form>

                <?php else: ?>

                    <p>Nenhum cliente encontrado com esse nome.</p>

                <?php endif; ?>

            </div>

        <?php endif; ?>

        <a href="equipamento.php" class="voltar">Cancelar</a>

    </div>

</body>
</html>
