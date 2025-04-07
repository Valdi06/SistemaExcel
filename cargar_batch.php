<?php
require_once 'Cliente.php';
$cliente = new Cliente();

if (isset($_POST['batch_id'])) {
    $batch_id = intval($_POST['batch_id']);
    $clientes = $cliente->obtenerClientesPorBatch($batch_id);
    echo json_encode($clientes);
}
