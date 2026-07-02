<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

    // Acepta pagos completados, pagos OXXO pendientes y pagos OXXO en revisión
    $sql = $con->prepare("SELECT count(id) FROM compra 
                          WHERE id_transaccion=? 
                          AND (status=? OR status=? OR status=? OR status=?)");
    $sql->execute([$id_transaccion, 'COMPLETED', 'approved', 'PENDIENTE_OXXO', 'EN_REVISION_OXXO']);

    if ($sql->fetchColumn() > 0) {

        $sql = $con->prepare("SELECT id, fecha, email, total, status, medio_pago 
                              FROM compra 
                              WHERE id_transaccion=? 
                              AND (status=? OR status=? OR status=? OR status=?) 
                              LIMIT 1");
        $sql->execute([$id_transaccion, 'COMPLETED', 'approved', 'PENDIENTE_OXXO', 'EN_REVISION_OXXO']);
        $row = $sql->fetch(PDO::FETCH_ASSOC);

        $idCompra = $row['id'];
        $status = $row['status'];
        $medio_pago = $row['medio_pago'];

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

                                <div class="alert alert-light border">
                                    Cuando hayas realizado el pago en OXXO, presiona el botón de abajo para avisar al administrador.
                                    Tu compra cambiará a estado <strong>en revisión</strong>.
                                </div>

                                <div class="mt-3">
                                    <form action="<?php echo SITE_URL; ?>clases/confirmar_pago_oxxo.php" method="post" style="display:inline-block;">
                                        <input type="hidden" name="id_transaccion" value="<?php echo $id_transaccion; ?>">
                                        <button type="submit" class="btn btn-success">
                                            Ya realicé mi pago
                                        </button>
                                    </form>

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

                <?php } elseif ($status == 'EN_REVISION_OXXO') { ?>

                    <div class="row mt-4">
                        <div class="col-md-8 col-sm-12">
                            <div class="alert alert-info">

                                <div class="mb-3">
                                    <span style="background-color:#e30613; color:white; padding:8px 18px; font-weight:bold; border-radius:6px; font-size:22px;">
                                        OXXO
                                    </span>
                                </div>

                                <h4 class="alert-heading">Pago enviado a revisión</h4>

                                <p>Tu aviso de pago fue registrado correctamente.</p>
                                <p>El administrador revisará la compra y confirmará el pago en el panel de administración.</p>

                                <hr>

                                <p class="mb-3">
                                    <strong>Referencia OXXO:</strong> <?php echo $id_transaccion; ?><br>
                                    <strong>Estado:</strong> En revisión por el administrador<br>
                                    <strong>Método de pago:</strong> OXXO<br>
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

                    <div class="row mt-4">
                        <div class="col-md-8 col-sm-12">
                            <div class="alert alert-success">
                                <h4 class="alert-heading">Compra completada</h4>
                                <p>Tu pago fue procesado correctamente.</p>
                            </div>
                        </div>
                    </div>

                <?php } ?>

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
                                } elseif ($status == 'EN_REVISION_OXXO') {
                                    echo 'En revisión por el administrador';
                                } else {
                                    echo 'Completado';
                                }
                            ?>
                        </p>
                    </div>
                </div>

                <?php if (($status == 'PENDIENTE_OXXO' || $status == 'EN_REVISION_OXXO') && isset($_SESSION['correo_oxxo_simulado'])) { ?>

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