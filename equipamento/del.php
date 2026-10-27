
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: equipamento.php");
    exit;
}

require_once "../conexao.php";

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    $conn->close();

    header("Location: equipamento.php?msg=erro");
    exit;
}

try {

    $sql = "DELETE FROM equipamento WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mensagem = "excluido";
    } else {
        $mensagem = "erro";
    }

    $stmt->close();

} catch (mysqli_sql_exception $e) {

    // Código 1451: o equipamento possui registros relacionados.
    if ($e->getCode() === 1451) {
        $mensagem = "relacionado";
    } else {
        error_log($e->getMessage());
        $mensagem = "erro";
    }

    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

$conn->close();

header("Location: equipamento.php?msg=" . $mensagem);
exit;

?>
