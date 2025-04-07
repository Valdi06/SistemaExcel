<?php
require_once 'Cliente.php';
$cliente = new Cliente();

if (isset($_POST['fecha'])) {
    $fecha = $_POST['fecha'];
    echo json_encode($cliente->obtenerBatchesPorFecha($fecha));
}
?>
