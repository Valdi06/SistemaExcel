<?php

include "Cliente.php";
$cliente = new Cliente();

// Inicializar variables
$saveSendStatus = "";
$saveSendMsjStatus = "";
$type_sent_msj = "text";
$status = "";
$msj = "";
$gsid = "";

// Sanitizar los datos recibidos
$message = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
$telefono = isset($_POST['telefonoSeleccionado']) ? trim($_POST['telefonoSeleccionado']) : '';
$source_phone = isset($_POST['source_phone']) ? trim($_POST['source_phone']) : '';
$nombre = isset($_POST['nombreSeleccionado']) ? trim($_POST['nombreSeleccionado']) : '';

$user_name = "chat";
$user_id = "1";

if (!empty($message) && !empty($telefono) && !empty($source_phone)) {
    // Send Message API GS
    $response_message = $cliente->API_send_message($source_phone, $telefono, $message);
    $json_resAPI = json_decode($response_message);
    $gsid = isset($json_resAPI->messageId) ? $json_resAPI->messageId : "";

    // Guardar mensaje en tabla sent_messages
    $save_message = $cliente->save_messagesent($message, $telefono, $source_phone, $response_message, $user_id, $user_name, $gsid);
    $json_response = json_decode($save_message);

    $saveSendStatus = ($json_response->status == "ok") ? "Success" : "Error";
    $saveSendMsjStatus = ($json_response->status == "ok") ? "Success" : $json_response->msj;

    $status = "ok";
    $msj = "Mensaje Enviado.";
} else {
    $message = "";
    $response_message = "";
    $status = "Error";
    $msj = "Mensaje no enviado, datos faltantes.";
    $gsid = "";
}

// variable para enviar en socket
$data = array(
    "chat_user" => $source_phone,
    "user_name" => "PideakyChat - " . $user_name,
    "destination_phone" => $telefono,
    "chat_message" => $message,
    "message_type" => "text",
    "type" => "message",
    "gsid" => $gsid,
    "urlfile" => "",
    "caption" => "",
    "context" => array(
        "context_id" => "",
        "contextgs_id" => ""
    )
);

// Echo con estructura original
echo json_encode(array(
                    "status" => $status,
                    "msj" => $msj,
                    "saveSendStatus" => $saveSendStatus,
                    "saveSendMsjStatus" => $saveSendMsjStatus,
                    "response_message" => $response_message,
                    "type_sent_msj" => $type_sent_msj,
                    "data" => $data
                ));


?>