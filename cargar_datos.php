<?php
require_once 'config.php';
require_once 'Cliente.php';

$batch_id = $_POST["batch_id"];

$cliente = new Cliente();
echo json_encode($cliente->obtenerClientesConMensajes($batch_id));

?>