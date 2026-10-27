
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
$busca = trim($_GET["busca"] ?? "");
$clientes = [];

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
}

if (!$id || $id <= 0) {
    $conn->close();
    header("Location: equipamento.php?msg=erro");
    exit;
}

// Recupera o equipamento e o cliente atual.
$sql = "SELECT
            e.id,
            e.nome,
            e.id_cliente,
            c.nome AS nome_cliente
        FROM equipamento e
        INNER JOIN cliente c ON e.id_cliente = c.id
        WHERE e.id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$equipamento = $resultado->fetch_assoc();

$stmt->close();

if (!$equipamento) {
    $conn->close();
    header("Location: equipamento.php?msg=erro");
    exit;
}

$nome = $equipamento["nome"];
$idClienteAtual = (int) $equipamento["id_cliente"];
$nomeClienteAtual = $equipamento["nome_cliente"];

// Pesquisa clientes para permitir a troca do relacionamento.
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

// Processa a edição.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $idCliente = filter_input(
        INPUT_POST,
        "id_cliente",
        FILTER_VALIDATE_INT
    );

    // Se não for escolhido outro cliente, mantém o relacionamento atual.
    if (!$idCliente) {
        $idCliente = $idClienteAtual;
    }

    if ($nome === "" || strlen($nome) > 100) {
        $erro = "Informe um nome de equipamento válido.";
    } else {

        // Confirma que o cliente existe e não é o administrador.
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
            $erro = "Selecione um cliente válido.";
        } else {

            $sql = "UPDATE equipamento
                    SET nome = ?, id_cliente = ?
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $nome, $idCliente, $id);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();

                header("Location: equipamento.php?msg=editado");
                exit;
            }

            error_log($stmt->error);
            $erro = "Não foi possível atualizar o equipamento.";

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

    <title>Editar equipamento - SAEP</title>

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

        .atual {
            padding: 12px;
            background: #f8fafc;
            border-radius: 4px;
        }
    </style>
</head>

<body>

    <nav>
        <a href="equipamento.php">← Voltar para equipamentos</a>
    </nav>

    <div class="container">

        <h1>Editar equipamento</h1>

        <?php if ($erro !== ""): ?>
            <p class="erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <p class="atual">
            Cliente atual:
            <strong>
                <?= htmlspecialchars($nomeClienteAtual, ENT_QUOTES, "UTF-8") ?>
            </strong>
        </p>

        <form action="edit.php" method="GET">

            <input
                type="hidden"
                name="id"
                value="<?= (int) $id ?>"
            >

            <label for="busca">Buscar outro cliente (opcional)</label>

            <input
                type="text"
                id="busca"
                name="busca"
                value="<?= htmlspecialchars($busca, ENT_QUOTES, "UTF-8") ?>"
                placeholder="Digite o nome ou parte do nome"
            >

            <button type="submit">Buscar cliente</button>

        </form>

        <?php if ($busca !== ""): ?>

            <h3>Resultados da busca</h3>

            <?php if (count($clientes) > 0): ?>

                <form action="edit.php" method="POST">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $id ?>"
                    >

                    <label for="id_cliente">Selecione o cliente</label>

                    <select id="id_cliente" name="id_cliente" required>

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
                        required
                    >

                    <button type="submit">Salvar alterações</button>

                </form>

            <?php else: ?>

                <p>Nenhum cliente encontrado com esse nome.</p>

            <?php endif; ?>

        <?php else: ?>

            <form action="edit.php" method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $id ?>"
                >

                <label for="nome">Nome do equipamento</label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="100"
                    value="<?= htmlspecialchars($nome, ENT_QUOTES, "UTF-8") ?>"
                    required
                >

                <button type="submit">Salvar alterações</button>

            </form>

        <?php endif; ?>

        <a href="equipamento.php" class="voltar">Cancelar</a>

    </div>

</body>
</html>
