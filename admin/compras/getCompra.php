<?php

/*Solicitud para consultar los datos de la compra*/

require '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$orden = $_POST['orden'] ?? null;

if ($orden == null) {
    exit;
}

$db = new Database();
$con = $db->conectar();

$sqlCompra = $con->prepare("SELECT 
        compra.id, 
        compra.id_transaccion, 
        compra.fecha, 
        compra.total, 
        compra.status,
        compra.medio_pago,
        CONCAT(clientes.nombres,' ',clientes.apellidos) AS cliente
    FROM compra
    INNER JOIN clientes ON compra.id_cliente = clientes.id
    WHERE compra.id_transaccion = ? 
    LIMIT 1");

$sqlCompra->execute([$orden]);
$rowCompra = $sqlCompra->fetch(PDO::FETCH_ASSOC);

if (!$rowCompra) {
    exit;
}

$idCompra = $rowCompra['id'];

$fecha = new DateTime($rowCompra['fecha']);
$fecha = $fecha->format('d-m-Y H:i');

// Formato del método de pago
$medioPago = $rowCompra['medio_pago'];

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

// Formato del estado
if ($rowCompra['status'] == 'PENDIENTE_OXXO') {
    $estadoTexto = 'Pendiente de pago OXXO';
    $estadoBadge = 'warning';
} elseif ($rowCompra['status'] == 'COMPLETED' || $rowCompra['status'] == 'approved') {
    $estadoTexto = 'Completado';
    $estadoBadge = 'success';
} else {
    $estadoTexto = $rowCompra['status'];
    $estadoBadge = 'secondary';
}

$sqlDetalle = $con->prepare("SELECT id, nombre, precio, cantidad FROM detalle_compra WHERE id_compra = ?");
$sqlDetalle->execute([$idCompra]);

$html = '';

$html .= '<div class="mb-3">';
$html .= '<p><strong>Cliente: </strong>' . $rowCompra['cliente'] . '</p>';
$html .= '<p><strong>Fecha: </strong>' . $fecha . '</p>';
$html .= '<p><strong>Orden / Referencia: </strong>' . $rowCompra['id_transaccion'] . '</p>';
$html .= '<p><strong>Total: </strong>$' . number_format($rowCompra['total'], 2, '.', ',') . '</p>';
$html .= '<p><strong>Método de pago: </strong><span class="badge bg-' . $metodoBadge . '">' . $metodoPagoTexto . '</span></p>';
$html .= '<p><strong>Estado: </strong><span class="badge bg-' . $estadoBadge . '">' . $estadoTexto . '</span></p>';
$html .= '</div>';

if ($rowCompra['status'] == 'PENDIENTE_OXXO') {
    $html .= '<div class="alert alert-warning">';
    $html .= '<strong>Pago pendiente:</strong> Esta compra fue generada con pago por OXXO simulado. ';
    $html .= 'El cliente debe usar la referencia <strong>' . $rowCompra['id_transaccion'] . '</strong> para realizar el pago.';
    $html .= '</div>';
}

$html .= '<table class="table table-bordered">
<thead>
<tr>
<th>Producto</th>
<th>Precio</th>
<th>Cantidad</th>
<th>Subtotal</th>
</tr>
</thead>';

$html .= '<tbody>';

while ($row = $sqlDetalle->fetch(PDO::FETCH_ASSOC)) {
    $precio = $row['precio'];
    $cantidad = $row['cantidad'];
    $subtotal = $precio * $cantidad;

    $html .= '<tr>';
    $html .= '<td>' . $row['nombre'] . '</td>';
    $html .= '<td>$' . number_format($precio, 2, '.', ',') . '</td>';
    $html .= '<td>' . $cantidad . '</td>';
    $html .= '<td>$' . number_format($subtotal, 2, '.', ',') . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

echo json_encode($html, JSON_UNESCAPED_UNICODE);