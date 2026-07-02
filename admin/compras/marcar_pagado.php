<?php

/* 
 * Confirmacion de pago OXXO por parte del administrador.
 * Cambia el estado de EN_REVISION_OXXO a COMPLETED.
 */

require '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$id_transaccion = $_POST['id_transaccion'] ?? '';

if ($id_transaccion == '') {
    header('Location: index.php');
    exit;
}

$db = new Database();
$con = $db->conectar();

try {
    $sql = $con->prepare("UPDATE compra 
                          SET status = ? 
                          WHERE id_transaccion = ? 
                          AND medio_pago = ? 
                          AND status = ?");
                          
    $sql->execute(['COMPLETED', $id_transaccion, 'oxxo', 'EN_REVISION_OXXO']);

    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    echo "<h3>Error al marcar pago como completado</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<a href='index.php'>Regresar a compras</a>";
    exit;
}
