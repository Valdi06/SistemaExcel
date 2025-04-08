<?php
include "Cliente.php";
$cliente = new Cliente();

$dateini = $_POST['dateini'];
$datefin = $_POST['datefin'];
$phone = $_POST['phone'];
$source_phone = $_POST['source_phone'];

$conversacion = $cliente->obtenerConversacionCompleta($dateini, $datefin, $phone, $source_phone);
echo json_encode($conversacion);
?>