<?php
require_once 'Cliente.php';

$telefono = isset($_POST['telefono']) ? $_POST['telefono'] : '';
$nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$template = isset($_POST['template']) ? $_POST['template'] : '';
$source_phone = isset($_POST['source_phone']) ? $_POST['source_phone'] : '';
$clienteid = isset($_POST['clienteid']) ? $_POST['clienteid'] : '';

$cliente = new Cliente();

$res_template = $cliente->enviar_plantilla($telefono, $source_phone);

echo $res_template;

?>