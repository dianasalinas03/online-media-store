<?php

/**
 * Confirmación simulada de pago OXXO por parte del cliente.
 * 
 * Cuando el cliente presiona el botón "Ya realicé mi pago",
 * este archivo cambia el estado de la compra de:
 * 
 * PENDIENTE_OXXO
 * 
 * a:
 * 
 * EN_REVISION_OXXO
 * 
 * para que después el administrador pueda revisarla y marcarla como pagada.
 */

require '../config/config.php';

if (!isset($_SESSION['user_cliente'])) {
    header("Location: ../login.php");
    exit;
}

$id_transaccion = $_POST['id_transaccion'] ?? '';

if ($id_transaccion == '') {
    header("Location: ../index.php");
    exit;
}

$db = new Database();
$con = $db->conectar();

$idCliente = $_SESSION['user_cliente'];

try {
    // Verificamos que la compra exista y que pertenezca al cliente actual
    $sql = $con->prepare("SELECT id, status, medio_pago 
                          FROM compra 
                          WHERE id_transaccion = ? 
                          AND id_cliente = ? 
                          LIMIT 1");
    $sql->execute([$id_transaccion, $idCliente]);
    $compra = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$compra) {
        header("Location: ../compras.php");
        exit;
    }

    // Solo se puede mandar a revisión si todavía está pendiente por OXXO
    if ($compra['status'] == 'PENDIENTE_OXXO' && $compra['medio_pago'] == 'oxxo') {
        $sqlUpdate = $con->prepare("UPDATE compra 
                                    SET status = ? 
                                    WHERE id_transaccion = ? 
                                    AND id_cliente = ?");
        $sqlUpdate->execute(['EN_REVISION_OXXO', $id_transaccion, $idCliente]);
    }

    // Regresa a la pantalla de completado, pero ahora mostrará "Pago enviado a revisión"
    header("Location: ../completado.php?key=" . urlencode($id_transaccion));
    exit;

} catch (PDOException $e) {
    echo "<h3>Error al confirmar pago OXXO</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<a href='../compras.php'>Regresar a mis compras</a>";
    exit;
}