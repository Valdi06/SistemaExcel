<?php
require_once 'Cliente.php';
$cliente = new Cliente();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_id'])) {
    $batchId = intval($_POST['batch_id']);
    echo json_encode($cliente->obtenerClientesPorBatch($batchId));
}
?>
