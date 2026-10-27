<?php

session_start();

if (!isset($_SESSION["id_cliente"])) {
    header("Location: ../login.php");
    exit;
}

// Expiração por inatividade: 30 minutos.
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

$conn->set_charset("utf8mb4");

$mensagens = [
    "criado" => "Ordem de serviço cadastrada com sucesso.",
    "excluido" => "Ordem de serviço excluída com sucesso.",
    "erro" => "Não foi possível realizar a operação.",
    "relacionado" => "Não foi possível excluir a ordem de serviço."
];

$mensagem = "";

if (isset($_GET["msg"]) && isset($mensagens[$_GET["msg"]])) {
    $mensagem = $mensagens[$_GET["msg"]];
}

// Token para proteger as operações realizadas por formulário.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Consulta as ordens de serviço e os respectivos clientes e equipamentos.
$sql = "SELECT
            s.id,
            s.data,
            c.nome AS cliente,
            e.nome AS equipamento
        FROM service s
        INNER JOIN cliente c
            ON s.id_cliente = c.id
        INNER JOIN equipamento e
            ON s.id_equipamento = e.id
        WHERE c.cpf <> '99988877722'
        ORDER BY s.data DESC, s.id DESC";

$resultado = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ordens de Serviço - SAEP</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f4f7;
            color: #333;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            padding: 15px 30px;
            background: #2563eb;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .painel {
            padding: 25px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px #00000010;
            overflow-x: auto;
        }

        .topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f8fafc;
        }

        .botao {
            display: inline-block;
            padding: 9px 12px;
            border: none;
            border-radius: 4px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .novo {
            background: #2563eb;
        }

        .excluir {
            background: #dc2626;
        }

        .mensagem {
            padding: 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 4px;
        }

        .erro {
            background: #fee2e2;
            color: #991b1b;
        }

        .vazio {
            padding: 20px 0;
            color: #666;
        }
    </style>
</head>

<body>

    <nav>
        <a href="../index.php">Início</a>
        <a href="../cliente/cliente.php">Clientes</a>
        <a href="../equipamento/equipamento.php">Equipamentos</a>
        <a href="service.php">Serviços</a>
    </nav>

    <main>
        <div class="painel">

            <div class="topo">
                <h1>Ordens de serviço</h1>

                <a href="add.php" class="botao novo">
                    + Nova ordem
                </a>
            </div>

            <?php if ($mensagem !== ""): ?>
                <p class="mensagem
                    <?= $_GET["msg"] === "erro" ? "erro" : "" ?>">
                    <?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Equipamento</th>
                            <th>Data da ordem</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($servico = $resultado->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?= (int) $servico["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $servico["cliente"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $servico["equipamento"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= date(
                                        "d/m/Y",
                                        strtotime($servico["data"])
                                    ) ?>
                                </td>

                                <td>
                                    <form
                                        action="del.php"
                                        method="POST"
                                        style="display:inline"
                                        onsubmit="return confirm(
                                            'Deseja realmente excluir esta ordem de serviço?'
                                        );"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $servico["id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars(
                                                $_SESSION["csrf_token"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="botao excluir"
                                        >
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    </tbody>
                </table>

            <?php elseif ($resultado): ?>

                <p class="vazio">
                    Nenhuma ordem de serviço cadastrada.
                </p>

            <?php else: ?>

                <p class="mensagem erro">
                    Não foi possível carregar as ordens de serviço.
                    Verifique a estrutura da tabela service.
                </p>

            <?php endif; ?>

        </div>
    </main>

</body>
</html>

<?php

$conn->close();

?>