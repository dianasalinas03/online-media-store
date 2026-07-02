<?php

/**
 * Pago OXXO simulado
 * Genera una referencia, guarda la compra como pendiente
 * y muestra un correo simulado al cliente.
 */

require '../config/config.php';

if (!isset($_SESSION['user_cliente'])) {
    header("Location: ../login.php?pago");
    exit;
}

if (!isset($_SESSION['carrito']['productos']) || empty($_SESSION['carrito']['productos'])) {
    header("Location: ../index.php");
    exit;
}

$db = new Database();
$con = $db->conectar();

$idCliente = $_SESSION['user_cliente'];
$productos = $_SESSION['carrito']['productos'];

// Obtener datos del cliente
$sqlCliente = $con->prepare("SELECT nombres, apellidos, email FROM clientes WHERE id = ? AND estatus = 1 LIMIT 1");
$sqlCliente->execute([$idCliente]);
$cliente = $sqlCliente->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    header("Location: ../login.php");
    exit;
}

$email = $cliente['email'];
$nombreCliente = $cliente['nombres'] . ' ' . $cliente['apellidos'];

// Generar referencia OXXO simulada
$referencia = 'OXXO-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
$fecha = date('Y-m-d H:i:s');
$fechaLimite = date('Y-m-d H:i:s', strtotime('+3 days'));
$status = 'PENDIENTE_OXXO';
$medioPago = 'oxxo';

$total = 0;
$listaProductos = [];

try {
    $con->beginTransaction();

    // Calcular total y preparar productos
    foreach ($productos as $idProducto => $cantidad) {
        $sqlProd = $con->prepare("SELECT id, nombre, precio, descuento, stock FROM productos WHERE id = ? AND activo = 1");
        $sqlProd->execute([$idProducto]);
        $producto = $sqlProd->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception("Producto no encontrado.");
        }

        if ($producto['stock'] < $cantidad) {
            throw new Exception("No hay suficiente stock para " . $producto['nombre']);
        }

        $precio = $producto['precio'];
        $descuento = $producto['descuento'];
        $precioDesc = $precio - (($precio * $descuento) / 100);
        $subtotal = $precioDesc * $cantidad;
        $total += $subtotal;

        $listaProductos[] = [
            'id' => $producto['id'],
            'nombre' => $producto['nombre'],
            'cantidad' => $cantidad,
            'precio' => $precioDesc,
            'subtotal' => $subtotal
        ];
    }

    // Guardar compra como pendiente
    $sqlCompra = $con->prepare("INSERT INTO compra 
        (fecha, status, email, id_cliente, total, id_transaccion, medio_pago) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sqlCompra->execute([$fecha, $status, $email, $idCliente, $total, $referencia, $medioPago]);

    $idCompra = $con->lastInsertId();

    // Guardar detalle de compra y restar stock
    foreach ($listaProductos as $producto) {
        $sqlDetalle = $con->prepare("INSERT INTO detalle_compra 
            (id_compra, id_producto, nombre, cantidad, precio) 
            VALUES (?, ?, ?, ?, ?)");
        $sqlDetalle->execute([
            $idCompra,
            $producto['id'],
            $producto['nombre'],
            $producto['cantidad'],
            $producto['precio']
        ]);

        $sqlStock = $con->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        $sqlStock->execute([$producto['cantidad'], $producto['id']]);
    }

    $con->commit();

    // Crear cuerpo del correo simulado
    $cuerpo = "<h2>Referencia de pago OXXO</h2>";
    $cuerpo .= "<p>Hola <b>$nombreCliente</b>, gracias por tu compra.</p>";
    $cuerpo .= "<p>Elegiste el método de pago por <b>OXXO</b>.</p>";
    $cuerpo .= "<p><b>Referencia:</b> $referencia</p>";
    $cuerpo .= "<p><b>Total a pagar:</b> " . MONEDA . number_format($total, 2, '.', ',') . "</p>";
    $cuerpo .= "<p><b>Fecha límite de pago:</b> $fechaLimite</p>";
    $cuerpo .= "<p>Tu pedido quedará registrado como <b>pendiente</b> hasta confirmar el pago.</p>";

    $cuerpo .= "<h3>Productos:</h3><ul>";
    foreach ($listaProductos as $producto) {
        $cuerpo .= "<li>" . $producto['cantidad'] . " x " . $producto['nombre'] . " - " . MONEDA . number_format($producto['subtotal'], 2, '.', ',') . "</li>";
    }
    $cuerpo .= "</ul>";

    // Guardamos el correo en sesión para mostrarlo como simulación
    $_SESSION['correo_oxxo_simulado'] = $cuerpo;
    $_SESSION['oxxo_fecha_limite'] = $fechaLimite;

    // Vaciar carrito
    unset($_SESSION['carrito']);

    // Redirigir a pantalla de confirmación
    header("Location: ../completado.php?key=" . urlencode($referencia));
    exit;

} catch (Exception $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }

    echo "<h3>Error al generar pago OXXO</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<a href='../checkout.php'>Regresar al carrito</a>";
    exit;
}