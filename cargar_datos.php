<?php
require_once 'config.php';
require_once 'Cliente.php';

$cliente = new Cliente();
echo json_encode($cliente->obtenerClientesConMensajes());

?>