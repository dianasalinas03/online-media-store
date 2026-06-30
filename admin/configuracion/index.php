<?php

/*Pantalla para mostrar el formulario de configuraciones*/

require '../config/config.php';
require '../../clases/cifrado.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$db = new Database();
$con = $db->conectar();

$sql = "SELECT nombre, valor FROM configuracion";
$resultado = $con->query($sql);
$datos = $resultado->fetchAll(PDO::FETCH_ASSOC);

$config = [];

foreach ($datos as $dato) {
    $config[$dato['nombre']] = $dato['valor'];
}

require '../header.php';

?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Settings</h1>

        <form action="guarda.php" method="post" id="form-config">

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-tab-pane" type="button" role="tab" aria-controls="general-tab-pane" aria-selected="true">General</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-tab-pane" type="button" role="tab" aria-controls="email-tab-pane" aria-selected="false">Correo electrónico</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="paypal-tab" data-bs-toggle="tab" data-bs-target="#paypal-tab-pane" type="button" role="tab" aria-controls="paypal-tab-pane" aria-selected="false">Paypal</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="mp-tab" data-bs-toggle="tab" data-bs-target="#mp-tab-pane" type="button" role="tab" aria-controls="mp-tab-pane" aria-selected="false">Mercado Pago</button>
                </li>
            </ul>

            <div class="tab-content mt-4" id="myTabContent">

                <!-- Tab General -->
                <div class="tab-pane fade show active" id="general-tab-pane" role="tabpanel" aria-labelledby="general-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="nombre">Name</label>
                            <input class="form-control" type="text" name="nombre" id="nombre" value="<?php echo $config['tienda_nombre']; ?>">
                        </div>

                        <div class="col-6">
                            <label for="telefono">Teléfono</label>
                            <input class="form-control" type="text" name="telefono" id="telefono" value="<?php echo $config['tienda_telefono']; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="moneda">Moneda</label>
                            <input class="form-control" type="text" name="moneda" id="moneda" value="<?php echo $config['tienda_moneda']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Tab Email -->
                <div class="tab-pane fade" id="email-tab-pane" role="tabpanel" aria-labelledby="email-tab" tabindex="1">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="smtp">SMTP</label>
                            <input class="form-control" type="text" name="smtp" id="smtp" value="<?php echo $config['correo_smtp']; ?>">
                        </div>

                        <div class="col-6">
                            <label for="puerto">Puerto</label>
                            <input class="form-control" type="text" name="puerto" id="puerto" value="<?php echo $config['correo_puerto']; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="email">Correo</label>
                            <input class="form-control" type="email" name="email" id="email" value="<?php echo $config['correo_email']; ?>">
                        </div>

                        <div class="col-6">
                            <label for="password">Contraseña</label>
                            <input class="form-control" type="password" name="password" id="password" value="<?php echo $config['correo_password']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Tab Paypal -->
                <div class="tab-pane fade" id="paypal-tab-pane" role="tabpanel" aria-labelledby="paypal-tab" tabindex="2">
                    <div class="row mb-3">
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="paypal_activo" name="paypal_activo" <?php echo ($config['paypal_activo'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="paypal_activo">
                                    Habilitar medio de pago
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-9">
                            <label for="paypal_cliente">Cliente ID</label>
                            <input class="form-control" type="text" name="paypal_cliente" id="paypal_cliente" value="<?php echo $config['paypal_cliente']; ?>">
                        </div>

                        <div class="col-3">
                            <label for="paypal_moneda">Moneda</label>
                            <input class="form-control" type="text" name="paypal_moneda" id="paypal_moneda" value="<?php echo $config['paypal_moneda']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Tab MercadoPago -->
                <div class="tab-pane fade" id="mp-tab-pane" role="tabpanel" aria-labelledby="mp-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="mp_activo" name="mp_activo" <?php echo ($config['mp_activo'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mp_activo">
                                    Habilitar medio de pago
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="mp_token">Token</label>
                            <input class="form-control" type="text" name="mp_token" id="mp_token" value="<?php echo $config['mp_token']; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="mp_clave">Clave pública</label>
                            <input class="form-control" type="text" name="mp_clave" id="mp_clave" value="<?php echo $config['mp_clave']; ?>">
                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-4">
                <div class="col-6">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </div>

        </form>

    </div>
</main>

<div class="modal fade" id="modalAlerta" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Alerta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Por favor, habilita al menos un medio de pago.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerar</button>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formulario = document.getElementById('form-config');

        formulario.addEventListener('submit', function(event) {
            const chkPaypal = document.getElementById('paypal_activo').checked;
            const chkMp = document.getElementById('mp_activo').checked;

            if (!chkPaypal && !chkMp) {
                event.preventDefault(); // Evitar el envío del formulario
                //alert('Por favor, selecciona al menos una opción como medio de pago.');
                const modalAlerta = new bootstrap.Modal('#modalAlerta')
                modalAlerta.show()
            }
        });
    });
</script>


<?php require '../footer.php'; ?>