<?php

/*Pantalla historial de compras*/

require '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$db = new Database();
$con = $db->conectar();

$sql = "SELECT 
            compra.id_transaccion, 
            compra.fecha, 
            compra.status, 
            compra.total, 
            compra.medio_pago, 
            CONCAT(clientes.nombres,' ',clientes.apellidos) AS cliente
        FROM compra
        INNER JOIN clientes ON compra.id_cliente = clientes.id
        ORDER BY compra.fecha DESC";

$resultado = $con->query($sql);

require '../header.php';

?>

<!-- Contenido -->
<main class="flex-shrink-0">
    <div class="container mt-3">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3>Sales</h3>
            <a class="btn btn-success" href="genera_reporte_compras.php">
                Sales Report
            </a>
        </div>

        <hr>

        <table id="datatablesSimple" class="table table-bordered table-sm">

            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width: 12%" data-sortable="false">Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($row = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>

                    <?php
                    // Método de pago
                    $medioPago = $row['medio_pago'];

                    if ($medioPago == 'oxxo') {
                        $metodoPagoTexto = 'OXXO';
                        $metodoBadge = 'danger';
                    } elseif ($medioPago == 'paypal') {
                        $metodoPagoTexto = 'PayPal';
                        $metodoBadge = 'primary';
                    } elseif ($medioPago == 'mercadopago' || $medioPago == 'mp') {
                        $metodoPagoTexto = 'Mercado Pago';
                        $metodoBadge = 'info';
                    } else {
                        $metodoPagoTexto = strtoupper($medioPago);
                        $metodoBadge = 'dark';
                    }

                    // Estado de compra
                    if ($row['status'] == 'PENDIENTE_OXXO') {
                        $estadoTexto = 'Pending OXXO';
                        $estadoBadge = 'warning';
                    } elseif ($row['status'] == 'EN_REVISION_OXXO') {
                        $estadoTexto = 'Waiting for review';
                        $estadoBadge = 'info';
                    } elseif ($row['status'] == 'COMPLETED' || $row['status'] == 'approved') {
                        $estadoTexto = 'Completed';
                        $estadoBadge = 'success';
                    } else {
                        $estadoTexto = $row['status'];
                        $estadoBadge = 'secondary';
                    }
                    ?>

                    <tr>
                        <td><?php echo $row['id_transaccion']; ?></td>
                        <td><?php echo $row['cliente']; ?></td>
                        <td><?php echo '$' . number_format($row['total'], 2, '.', ','); ?></td>

                        <td>
                            <span class="badge bg-<?php echo $metodoBadge; ?>">
                                <?php echo $metodoPagoTexto; ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-<?php echo $estadoBadge; ?>">
                                <?php echo $estadoTexto; ?>
                            </span>
                        </td>

                        <td><?php echo $row['fecha']; ?></td>

                        <td>
                            <button 
                                type="button" 
                                class="btn btn-sm btn-primary mb-1" 
                                data-bs-toggle="modal" 
                                data-bs-target="#detalleModal" 
                                data-bs-orden="<?php echo $row['id_transaccion']; ?>">
                                <i class="fas fa-shopping-basket"></i> View
                            </button>

                            <?php if ($row['status'] == 'EN_REVISION_OXXO') { ?>
                                <form action="marcar_pagado.php" method="post" style="display:inline-block;">
                                    <input type="hidden" name="id_transaccion" value="<?php echo $row['id_transaccion']; ?>">
                                    <button 
                                        type="submit" 
                                        class="btn btn-sm btn-success mb-1"
                                        onclick="return confirm('¿Confirmar que este pago OXXO ya fue recibido?');">
                                        Mark as Paid
                                    </button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>

                <?php } ?>

            </tbody>
        </table>
    </div>
</main>

<!-- Modal -->
<div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h1 class="modal-title fs-5" id="detalleModalLabel">Order details</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<script>
    const detalleModal = document.getElementById('detalleModal')

    detalleModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget
        const orden = button.getAttribute('data-bs-orden')
        const modalBody = detalleModal.querySelector('.modal-body')

        const url = '<?php echo ADMIN_URL; ?>compras/getCompra.php'

        let formData = new FormData()
        formData.append('orden', orden)

        fetch(url, {
                method: 'post',
                body: formData,
            })
            .then((resp) => resp.json())
            .then(function(data) {
                modalBody.innerHTML = data
            })
    })

    detalleModal.addEventListener('hide.bs.modal', event => {
        const modalBody = detalleModal.querySelector('.modal-body')
        modalBody.innerHTML = ''
    })
</script>

<?php include '../footer.php'; ?>