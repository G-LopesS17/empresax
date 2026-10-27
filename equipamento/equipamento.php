
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

$mensagens = [
    "criado" => "Equipamento cadastrado com sucesso.",
    "editado" => "Equipamento atualizado com sucesso.",
    "excluido" => "Equipamento excluído com sucesso.",
    "relacionado" => "Não foi possível excluir: existem ordens de serviço relacionadas.",
    "erro" => "Não foi possível realizar a operação."
];

$mensagem = "";

if (isset($_GET["msg"]) && isset($mensagens[$_GET["msg"]])) {
    $mensagem = $mensagens[$_GET["msg"]];
}

// Consulta os equipamentos e os respectivos clientes.
$sql = "SELECT
            e.id,
            e.nome AS equipamento,
            c.id AS id_cliente,
            c.nome AS cliente
        FROM equipamento e
        INNER JOIN cliente c ON e.id_cliente = c.id
        ORDER BY e.id DESC";

$resultado = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Equipamentos - SAEP</title>

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
            flex-wrap: wrap;
            gap: 20px;
            padding: 15px 30px;
            background: #2563eb;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
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
            overflow-x: auto;
            box-shadow: 0 2px 8px #00000010;
        }

        .topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
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
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .novo {
            background: #2563eb;
        }

        .editar {
            background: #d97706;
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
    </style>
</head>

<body>

    <nav>
        <a href="../index.php">Início</a>
        <a href="../cliente/cliente.php">Clientes</a>
        <a href="equipamento.php">Equipamentos</a>
        <a href="../ordem/service.php">Serviços</a>
    </nav>

    <main>
        <div class="painel">

            <div class="topo">
                <h1>Equipamentos cadastrados</h1>

                <a href="add.php" class="botao novo">
                    + Novo equipamento
                </a>
            </div>

            <?php if ($mensagem !== ""): ?>
                <p class="mensagem">
                    <?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Equipamento</th>
                            <th>Cliente relacionado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($equipamento = $resultado->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?= (int) $equipamento["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($equipamento["equipamento"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($equipamento["cliente"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <a
                                        href="edit.php?id=<?= (int) $equipamento["id"] ?>"
                                        class="botao editar"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        action="del.php"
                                        method="POST"
                                        style="display:inline"
                                        onsubmit="return confirm('Deseja realmente excluir este equipamento?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $equipamento["id"] ?>"
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

            <?php else: ?>

                <p>Nenhum equipamento cadastrado.</p>

            <?php endif; ?>

        </div>
    </main>

</body>
</html>

<?php

$conn->close();

?>
