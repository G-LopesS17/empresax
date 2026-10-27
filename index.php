
<?php

session_start();

// Verifica se existe um cliente autenticado.
if (!isset($_SESSION["id_cliente"])) {
    header("Location: login.php");
    exit;
}

// Define o tempo máximo de inatividade: 30 minutos.
$tempoExpiracao = 1800;

// Verifica o tempo da última atividade.
if (
    isset($_SESSION["ultima_atividade"]) &&
    (time() - $_SESSION["ultima_atividade"]) > $tempoExpiracao
) {
    // Encerra a sessão expirada.
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $parametros = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $parametros["path"],
            $parametros["domain"],
            $parametros["secure"],
            $parametros["httponly"]
        );
    }

    session_destroy();

    header("Location: login.php?erro=1");
    exit;
}

// Atualiza o horário da última atividade.
$_SESSION["ultima_atividade"] = time();

// Recupera o nome do cliente autenticado.
$nomeCliente = $_SESSION["nome_cliente"] ?? "Cliente";

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início - SAEP</title>

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

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 15px 30px;
            background-color: #2563eb;
            color: #ffffff;
        }

        nav h2 {
            margin: 0;
            font-size: 22px;
        }

        nav .links {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        nav a {
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .painel {
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .painel h1 {
            margin-top: 0;
            color: #2563eb;
        }

        .painel p {
            line-height: 1.6;
        }

        @media (max-width: 600px) {
            nav {
                padding: 15px;
            }

            nav .links {
                gap: 12px;
            }

            main {
                margin: 20px auto;
            }
        }
    </style>
</head>

<body>

    <nav>

        <h2>SAEP</h2>

        <div class="links">
            <a href="index.php">Início</a>
            <a href="cliente\cliente.php">Clientes</a>
            <a href="equipamento\equipamento.php">Equipamentos</a>
            <a href="ordem\service.php">Serviços</a>
        </div>

    </nav>

    <main>

        <div class="painel">

            <h1>Bem-vindo ao SAEP!</h1>

            <p>
                Olá,
                <strong>
                    <?= htmlspecialchars($nomeCliente, ENT_QUOTES, "UTF-8") ?>
                </strong>!
            </p>

            <p>
                Este é o sistema de gerenciamento da assistência técnica
                de equipamentos de informática.
            </p>

            <p>
                Utilize a barra de navegação superior para acessar
                as funcionalidades de clientes, equipamentos e serviços.
            </p>

        </div>

    </main>

</body>
</html>
