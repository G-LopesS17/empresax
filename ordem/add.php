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

$cpfAdmin = "99988877722";
$erro = "";

// Token de segurança dos formulários.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Busca os clientes disponíveis.
$sqlClientes = "SELECT id, nome
                FROM cliente
                WHERE cpf <> ?
                ORDER BY nome ASC";

$stmtClientes = $conn->prepare($sqlClientes);
$stmtClientes->bind_param("s", $cpfAdmin);
$stmtClientes->execute();

$resultadoClientes = $stmtClientes->get_result();

// Busca os equipamentos e seus respectivos clientes.
$sqlEquipamentos = "SELECT
                        e.id,
                        e.nome AS equipamento,
                        e.id_cliente
                    FROM equipamento e
                    INNER JOIN cliente c ON e.id_cliente = c.id
                    WHERE c.cpf <> ?
                    ORDER BY e.nome ASC";

$stmtEquipamentos = $conn->prepare($sqlEquipamentos);
$stmtEquipamentos->bind_param("s", $cpfAdmin);
$stmtEquipamentos->execute();

$resultadoEquipamentos = $stmtEquipamentos->get_result();

// Processa o cadastro da ordem de serviço.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    $idCliente = filter_input(
        INPUT_POST,
        "id_cliente",
        FILTER_VALIDATE_INT
    );

    $idEquipamento = filter_input(
        INPUT_POST,
        "id_equipamento",
        FILTER_VALIDATE_INT
    );

    $data = $_POST["data"] ?? "";

    // Valida o token enviado pelo formulário.
    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $erro = "Solicitação inválida. Atualize a página e tente novamente.";
    } elseif (
        !$idCliente ||
        !$idEquipamento ||
        !is_string($data)
    ) {
        $erro = "Selecione um cliente, um equipamento e uma data válida.";
    } else {

        // Valida o formato e a existência da data.
        $dataObjeto = DateTimeImmutable::createFromFormat(
            "!Y-m-d",
            $data
        );

        if (
            !$dataObjeto ||
            $dataObjeto->format("Y-m-d") !== $data
        ) {
            $erro = "Informe uma data válida.";
        } else {

            // Confirma que o equipamento pertence ao cliente selecionado.
            $sqlValidacao = "SELECT e.id
                             FROM equipamento e
                             INNER JOIN cliente c
                                 ON e.id_cliente = c.id
                             WHERE e.id = ?
                               AND e.id_cliente = ?
                               AND c.cpf <> ?
                             LIMIT 1";

            $stmtValidacao = $conn->prepare($sqlValidacao);
            $stmtValidacao->bind_param(
                "iis",
                $idEquipamento,
                $idCliente,
                $cpfAdmin
            );

            $stmtValidacao->execute();
            $resultadoValidacao = $stmtValidacao->get_result();

            if ($resultadoValidacao->num_rows !== 1) {

                $erro = "O equipamento selecionado não pertence ao cliente informado.";

            } else {

                // Registra a ordem usando o equipamento selecionado.
               $sqlInserir = "INSERT INTO service
                   (id_cliente, id_equipamento, `data`)
               VALUES (?, ?, ?)";

$stmtInserir = $conn->prepare($sqlInserir);

$stmtInserir->bind_param(
    "iis",
    $idCliente,
    $idEquipamento,
    $data
);

                if ($stmtInserir->execute()) {
                    $stmtInserir->close();
                    $stmtValidacao->close();
                    $stmtClientes->close();
                    $stmtEquipamentos->close();
                    $conn->close();

                    header("Location: service.php?msg=criado");
                    exit;
                }

                $erro = "Não foi possível cadastrar a ordem de serviço.";

                $stmtInserir->close();
            }

            $stmtValidacao->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nova Ordem de Serviço - SAEP</title>

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
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }

        .painel {
            padding: 25px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px #00000010;
        }

        .campo {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            font-size: 15px;
        }

        input:focus, select:focus {
            outline: 2px solid #93c5fd;
            border-color: #2563eb;
        }

        .acoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .botao {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .salvar {
            background: #2563eb;
        }

        .cancelar {
            background: #6b7280;
        }

        .mensagem {
            padding: 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .ajuda {
            margin-top: 6px;
            color: #666;
            font-size: 13px;
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

            <h1>Nova ordem de serviço</h1>

            <?php if ($erro !== ""): ?>
                <p class="mensagem">
                    <?= htmlspecialchars(
                        $erro,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>
            <?php endif; ?>

            <form action="add.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION["csrf_token"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

                <div class="campo">
                    <label for="id_cliente">Cliente</label>

                    <select
                        name="id_cliente"
                        id="id_cliente"
                        required
                    >
                        <option value="">Selecione um cliente</option>

                        <?php while (
                            $cliente = $resultadoClientes->fetch_assoc()
                        ): ?>

                            <option
                                value="<?= (int) $cliente["id"] ?>"
                                <?= (
                                    isset($idCliente) &&
                                    $idCliente == $cliente["id"]
                                ) ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars(
                                    $cliente["nome"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                    <p class="ajuda">
                        Selecione o cliente para carregar seus equipamentos.
                    </p>
                </div>

                <div class="campo">
                    <label for="id_equipamento">Equipamento</label>

                    <select
                        name="id_equipamento"
                        id="id_equipamento"
                        required
                        disabled
                    >
                        <option value="">
                            Selecione primeiro um cliente
                        </option>

                        <?php while (
                            $equipamento = $resultadoEquipamentos->fetch_assoc()
                        ): ?>

                            <option
                                value="<?= (int) $equipamento["id"] ?>"
                                data-cliente="<?= (int) $equipamento["id_cliente"] ?>"
                                <?= (
                                    isset($idEquipamento) &&
                                    $idEquipamento == $equipamento["id"]
                                ) ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars(
                                    $equipamento["equipamento"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>
                </div>

                <div class="campo">
                    <label for="data">Data da ordem de serviço</label>

                    <input
                        type="date"
                        name="data"
                        id="data"
                        value="<?= htmlspecialchars(
                            $data ?? date("Y-m-d"),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        required
                    >
                </div>

                <div class="acoes">
                    <button
                        type="submit"
                        class="botao salvar"
                    >
                        Cadastrar ordem
                    </button>

                    <a
                        href="service.php"
                        class="botao cancelar"
                    >
                        Cancelar
                    </a>
                </div>

            </form>

        </div>
    </main>

    <script>
        const clienteSelect = document.getElementById("id_cliente");
        const equipamentoSelect = document.getElementById("id_equipamento");

        function filtrarEquipamentos() {
            const idCliente = clienteSelect.value;
            const equipamentoAnterior = equipamentoSelect.value;
            let quantidade = 0;
            let equipamentoAnteriorValido = false;

            equipamentoSelect.value = "";

            Array.from(equipamentoSelect.options).forEach(
                function (opcao, indice) {

                    if (indice === 0) {
                        opcao.hidden = false;
                        opcao.disabled = false;
                        return;
                    }

                    const pertenceAoCliente =
                        idCliente !== "" &&
                        opcao.dataset.cliente === idCliente;

                    opcao.hidden = !pertenceAoCliente;
                    opcao.disabled = !pertenceAoCliente;

                    if (pertenceAoCliente) {
                        quantidade++;

                        if (opcao.value === equipamentoAnterior) {
                            equipamentoAnteriorValido = true;
                        }
                    }
                }
            );

            equipamentoSelect.disabled = idCliente === "";

            if (idCliente === "") {
                equipamentoSelect.options[0].text =
                    "Selecione primeiro um cliente";
            } else if (quantidade === 0) {
                equipamentoSelect.options[0].text =
                    "Este cliente não possui equipamentos";
            } else {
                equipamentoSelect.options[0].text =
                    "Selecione um equipamento";
            }

            if (equipamentoAnteriorValido) {
                equipamentoSelect.value = equipamentoAnterior;
            }
        }

        clienteSelect.addEventListener("change", function () {
            filtrarEquipamentos();
        });

        filtrarEquipamentos();
    </script>

</body>
</html>

<?php

$stmtClientes->close();
$stmtEquipamentos->close();
$conn->close();

?>