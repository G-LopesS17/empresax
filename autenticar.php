
<?php

session_start();

require_once "conexao.php";

// Aceita somente requisições enviadas pelo formulário.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// Recupera os dados enviados pelo formulário.
$cpf = trim($_POST["cpf"] ?? "");
$senhaDigitada = $_POST["senha"] ?? "";

// Verifica se o CPF possui exatamente 11 números.
if (!preg_match("/^[0-9]{11}$/", $cpf) || $senhaDigitada === "") {
    header("Location: login.php?erro=1");
    exit;
}

// Consulta o cliente pelo CPF.
$sql = "SELECT id, nome, cpf, senha
        FROM cliente
        WHERE cpf = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Erro ao preparar consulta de autenticação.");
    http_response_code(500);
    exit("Não foi possível realizar a autenticação.");
}

$stmt->bind_param("s", $cpf);
$stmt->execute();

$resultado = $stmt->get_result();

// Verifica se o cliente existe.
if ($resultado->num_rows === 1) {

    $cliente = $resultado->fetch_assoc();

    // Verifica a senha armazenada no banco.
    if (password_verify($senhaDigitada, $cliente["senha"])) {

        // Evita reutilização do identificador anterior da sessão.
        session_regenerate_id(true);

        // Armazena os dados necessários para identificar o cliente.
        $_SESSION["id_cliente"] = $cliente["id"];
        $_SESSION["nome_cliente"] = $cliente["nome"];
        $_SESSION["cpf_cliente"] = $cliente["cpf"];

        // Define o horário da última atividade.
        $_SESSION["ultima_atividade"] = time();

        header("Location: index.php");
        exit;
    }
}

// Caso o CPF não exista ou a senha esteja incorreta.
$stmt->close();
$conn->close();

header("Location: login.php?erro=1");
exit;

?>
