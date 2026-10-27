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

// Exclusão permitida somente por POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: service.php");
    exit;
}

// Valida o token de segurança.
$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    $conn->close();

    header("Location: service.php?msg=erro");
    exit;
}

// Valida o ID recebido.
$id = filter_input(
    INPUT_POST,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    $conn->close();

    header("Location: service.php?msg=erro");
    exit;
}

// Exclui somente a ordem de serviço informada.
$sql = "DELETE FROM service WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows === 1) {

    $stmt->close();
    $conn->close();

    header("Location: service.php?msg=excluido");
    exit;

} else {

    $stmt->close();
    $conn->close();

    header("Location: service.php?msg=erro");
    exit;
}

?>