<?php
require_once 'Cliente.php';

$batch_id = $_POST["batch_id"];
$cliente = new Cliente();
$resumen = $cliente->obtenerResumenPorBatch($batch_id);
echo json_encode($resumen);

?>
