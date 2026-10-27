
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

$cpfAdmin = "99988877722";
$mensagem = "";

$mensagens = [
    "criado" => "Cliente cadastrado com sucesso.",
    "editado" => "Cliente atualizado com sucesso.",
    "excluido" => "Cliente excluído com sucesso.",
    "erro" => "Não foi possível realizar a operação.",
    "duplicado" => "Este CPF já está cadastrado.",
    "relacionado" => "Não é possível excluir: existem registros relacionados."
];

if (isset($_GET["msg"]) && isset($mensagens[$_GET["msg"]])) {
    $mensagem = $mensagens[$_GET["msg"]];
}

// O usuário administrativo não aparece na listagem.
$sql = "SELECT id, nome, cpf, telefone
        FROM cliente
        WHERE cpf <> ?
        ORDER BY nome ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cpfAdmin);
$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clientes - SAEP</title>

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

        .vazio {
            padding: 20px 0;
            color: #666;
        }
    </style>
</head>

<body>

    <nav>
        <a href="../index.php">Início</a>
        <a href="cliente.php">Clientes</a>
        <a href="../equipamento/equipamento.php">Equipamentos</a>
        <a href="../ordem/service.php">Serviços</a>
    </nav>

    <main>
        <div class="painel">

            <div class="topo">
                <h1>Clientes cadastrados</h1>

                <a href="add.php" class="botao">
                    + Novo cliente
                </a>
            </div>

            <?php if ($mensagem !== ""): ?>
                <p class="mensagem">
                    <?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if ($resultado->num_rows > 0): ?>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($cliente = $resultado->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?= (int) $cliente["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente["nome"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente["cpf"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente["telefone"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <a
                                        href="edit.php?id=<?= (int) $cliente["id"] ?>"
                                        class="botao editar"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        action="del.php"
                                        method="POST"
                                        style="display:inline"
                                        onsubmit="return confirm('Deseja realmente excluir este cliente?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $cliente["id"] ?>"
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

                <p class="vazio">
                    Nenhum cliente cadastrado.
                </p>

            <?php endif; ?>

        </div>
    </main>

</body>
</html>

<?php

$stmt->close();
$conn->close();

?>