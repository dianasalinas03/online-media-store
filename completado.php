<?php

/**
 * Script para mostrar los detalles del pago
 */

require 'config/config.php';

$id_transaccion = isset($_GET['key']) ? $_GET['key'] : '';

$error = '';

if ($id_transaccion == '') {
    $error = 'Error al procesar la petición';
} else {

    $db = new Database();
    $con = $db->conectar();

    // Acepta pagos completados de PayPal/Mercado Pago y pagos OXXO pendientes
    $sql = $con->prepare("SELECT count(id) FROM compra 
                          WHERE id_transaccion=? 
                          AND (status=? OR status=? OR status=?)");
    $sql->execute([$id_transaccion, 'COMPLETED', 'approved', 'PENDIENTE_OXXO']);

    if ($sql->fetchColumn() > 0) {

        // Se obtiene la información de la compra
        $sql = $con->prepare("SELECT id, fecha, email, total, status, medio_pago 
                              FROM compra 
                              WHERE id_transaccion=? 
                              AND (status=? OR status=? OR status=?) 
                              LIMIT 1");
        $sql->execute([$id_transaccion, 'COMPLETED', 'approved', 'PENDIENTE_OXXO']);
        $row = $sql->fetch(PDO::FETCH_ASSOC);

        $idCompra = $row['id'];
        $total = $row['total'];
        $fecha = $row['fecha'];
        $status = $row['status'];
        $medio_pago = $row['medio_pago'];

        // Se obtienen los productos comprados
        $sqlDet = $con->prepare("SELECT nombre, precio, cantidad FROM detalle_compra WHERE id_compra=?");
        $sqlDet->execute([$idCompra]);

    } else {
        $error = "Error al comprobar la compra";
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda en línea</title>

    <link href="<?php echo SITE_URL; ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>css/estilos.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <?php include 'menu.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container">

            <?php if (strlen($error) > 0) { ?>

                <div class="row mt-4">
                    <div class="col">
                        <h3><?php echo $error; ?></h3>
                    </div>
                </div>

            <?php } else { ?>

                <?php if ($status == 'PENDIENTE_OXXO') { ?>

                    <!-- Mensaje especial para pago por OXXO -->
                    <div class="row mt-4">
                        <div class="col-md-8 col-sm-12">
                            <div class="alert alert-warning">

                                <div class="mb-3">
                                    <span style="background-color:#e30613; color:white; padding:8px 18px; font-weight:bold; border-radius:6px; font-size:22px;">
                                        OXXO
                                    </span>
                                </div>

                                <h4 class="alert-heading">Pago OXXO generado</h4>

                                <p>Tu compra fue registrada correctamente como pago pendiente.</p>
                                <p>Usa la siguiente referencia para realizar tu pago en OXXO.</p>

                                <hr>

                                <p class="mb-3">
                                    <strong>Referencia OXXO:</strong> <?php echo $id_transaccion; ?><br>
                                    <strong>Estado:</strong> Pendiente de pago<br>
                                    <strong>Método de pago:</strong> OXXO<br>

                                    <?php if (isset($_SESSION['oxxo_fecha_limite'])) { ?>
                                        <strong>Fecha límite de pago:</strong> <?php echo $_SESSION['oxxo_fecha_limite']; ?><br>
                                    <?php } ?>
                                </p>

                                <div class="mt-3">
                                    <a href="<?php echo SITE_URL; ?>" class="btn btn-dark">
                                        Volver a la tienda
                                    </a>

                                    <a href="<?php echo SITE_URL; ?>compras.php" class="btn btn-outline-dark">
                                        Ver mis compras
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>

                <?php } else { ?>

                    <!-- Mensaje para pago completado normal -->
                    <div class="row mt-4">
                        <div class="col-md-8 col-sm-12">
                            <div class="alert alert-success">
                                <h4 class="alert-heading">Compra completada</h4>
                                <p>Tu pago fue procesado correctamente.</p>
                            </div>
                        </div>
                    </div>

                <?php } ?>

                <!-- Datos generales de la compra -->
                <div class="row mt-3">
                    <div class="col-md-8 col-sm-12">
                        <h4>Resumen de compra</h4>

                        <p>
                            <strong>Folio / Referencia:</strong> <?php echo $id_transaccion; ?><br>
                            <strong>Fecha de compra:</strong> <?php echo $row['fecha']; ?><br>
                            <strong>Total:</strong> <?php echo MONEDA . number_format($row['total'], 2, '.', ','); ?><br>
                            <strong>Correo:</strong> <?php echo $row['email']; ?><br>
                            <strong>Método de pago:</strong> <?php echo strtoupper($medio_pago); ?><br>
                            <strong>Estado:</strong> 
                            <?php 
                                if ($status == 'PENDIENTE_OXXO') {
                                    echo 'Pendiente de pago';
                                } else {
                                    echo 'Completado';
                                }
                            ?>
                        </p>
                    </div>
                </div>

                <!-- Correo simulado para OXXO -->
                <?php if ($status == 'PENDIENTE_OXXO' && isset($_SESSION['correo_oxxo_simulado'])) { ?>

                    <div class="row mt-4">
                        <div class="col-md-8 col-sm-12">
                            <div class="card">
                                <div class="card-header">
                                    Correo simulado enviado al cliente
                                </div>

                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <strong>Nota:</strong> Este correo se muestra de manera simulada para fines académicos.
                                        En un entorno real, sería enviado al cliente mediante SMTP.
                                    </div>

                                    <?php echo $_SESSION['correo_oxxo_simulado']; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php } ?>

                <!-- Productos comprados -->
                <div class="row mt-4">
                    <div class="col-md-8 col-sm-12">
                        <h4>Productos comprados</h4>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cantidad</th>
                                    <th>Producto</th>
                                    <th>Importe</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($row_det = $sqlDet->fetch(PDO::FETCH_ASSOC)) {
                                    $importe = $row_det['cantidad'] * $row_det['precio']; 
                                ?>
                                    <tr>
                                        <td><?php echo $row_det['cantidad']; ?></td>
                                        <td><?php echo $row_det['nombre']; ?></td>
                                        <td><?php echo MONEDA . number_format($importe, 2, '.', ','); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php } ?>

        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>

</body>

</html>