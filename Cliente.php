<?php
require_once 'config.php'; // Incluir conexión
$baseurl = "./logs/";
class Cliente {
    private $conn;
    private $telefonosProcesados = []; // Para evitar duplicados en el mismo archivo

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Verifica si el teléfono ya fue enviado HOY (pero permite si la hora es diferente)
    public function telefonoEnviadoHoy($telefono) {
        $hoy = date("Y-m-d"); // Obtiene la fecha actual
        $query = "SELECT id FROM clientes WHERE telefono = ? AND fecha_envio = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $telefono, $hoy);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0; // Devuelve true si ya existe hoy
    }

    // Guarda un cliente en la base de datos evitando duplicados dentro del mismo archivo Excel
    public function guardarCliente($nombre, $address, $email, $web, $telefono, $response, $source_phone, $batch_id) {
        // Evitar duplicados en el mismo archivo Excel
        if (in_array($telefono, $this->telefonosProcesados)) {
            return false; // Ya se procesó en este archivo, no lo guardamos ni mostramos
        }

        // Evitar duplicados exactos en la base de datos
        if ($this->telefonoEnviadoHoy($telefono)) {
            return false; // Ya existe hoy con cualquier hora
        }

        // Insertar el cliente
        $query = "INSERT INTO clientes (nombre, `address`, email, web, telefono, response, source_phone, batch_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssssi", $nombre, $address, $email, $web, $telefono, $response, $source_phone, $batch_id);
        $resultado = $stmt->execute();

        if ($resultado) {
            $this->telefonosProcesados[] = $telefono; // Registrar el teléfono para evitar duplicados en el mismo archivo
        }

        return $resultado;
    }

    public function obtenerClientes($filtro = "todos") {
        $query = "SELECT id, nombre, `address`, email, web, CONCAT( IF ( LEFT ( c.telefono, 3 ) = '521', '', '521' ), c.telefono ) as telefono, fecha_envio, response FROM clientes";
    
        if ($filtro === "enviados") {
            $query .= " WHERE response = 'Enviado'";
        } elseif ($filtro === "no_enviados") {
            $query .= " WHERE response = 'No Enviado'";
        }
    
        $query .= " ORDER BY fecha_envio DESC";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
    
        return $clientes;
    }

    public function obtenerClientesConMensajes($batch_id, $estado = null) {

        $whereEstado = "";
        if ($estado === "failed") {
            $whereEstado = " AND cf.failed IS NOT NULL ";
        } elseif ($estado === "sent") {
            $whereEstado = " AND cf.sent IS NOT NULL ";
        } elseif ($estado === "delivered") {
            $whereEstado = " AND cf.delivered IS NOT NULL ";
        } elseif ($estado === "seen") {
            $whereEstado = " AND cf.seen IS NOT NULL ";
        } elseif ($estado === "reply") {
            $whereEstado = " AND c.replymessage_id IS NOT NULL AND c.replymessage_id != '' AND c.replymessage_id != 0";
        }

        $query = "
            SELECT
                c.id,
                c.nombre,
                c.address,
                c.email,
                c.web,
                CONCAT( IF ( LEFT ( c.telefono, 3 ) = '521', '', '521' ), c.telefono ) as telefono,
                c.fecha_envio,
                c.response,
                c.source_phone,
                c.batch_id,
                p.origin,
                cf.id as customerfile_id,
                COALESCE (
                    ( SELECT message_received FROM messageschb WHERE messageschb.id = p.message_id AND p.origin = 'received' AND p.message_type = 'text' ),
                    ( SELECT message_sent FROM sent_messages WHERE sent_messages.id = p.message_id AND p.origin = 'sent' AND p.message_type = 'text' ),
                    ( SELECT '📎 Archivo Adjunto' FROM customerfiles WHERE customerfiles.id = p.message_id AND p.message_type = 'file' ),
                    'Sin mensajes' 
                ) AS ultimo_mensaje,
                p.lasttimestamp AS mensaje_fecha,-- Determinar el estado del mensaje basado en las columnas
            CASE
                    WHEN cf.seen IS NOT NULL THEN
                    'seen' 
                    WHEN cf.delivered IS NOT NULL THEN
                    'delivered' 
                    WHEN cf.sent IS NOT NULL THEN
                    'sent' 
                    WHEN cf.failed IS NOT NULL THEN
                    'failed' 
                    WHEN cf.queued IS NOT NULL THEN
                    'queued' ELSE NULL 
                END AS mensaje_estado 
            FROM
                clientes c
                LEFT JOIN phones p ON CONCAT( IF ( LEFT ( c.telefono, 3 ) = '521', '', '521' ), c.telefono ) = p.phone 
                AND c.source_phone = p.source_phone
                LEFT JOIN sent_messages sm ON sm.id = p.message_id 
                AND p.origin = 'sent' 
                AND p.message_type = 'text'
                LEFT JOIN customerfiles cf ON cf.gsid = c.response AND c.response != '' AND c.response IS NOT NULL 
            WHERE
                c.batch_id = ? $whereEstado 
            ORDER BY
                p.lasttimestamp DESC
        ";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $res_recientes = $this->buscar_recientes($row["telefono"], $row["id"]);
            $json_recientes = json_decode($res_recientes);
            // Formatear la fecha del mensaje
            $row['numRecientes'] = $json_recientes->num;
            $row['reciente'] = $json_recientes->compararFecha;
            $row['lasttimestamp'] = $row['mensaje_fecha'];
            $row['mensaje_fecha'] = $this->formatearFecha($row['mensaje_fecha']);
            $clientes[] = $row;
        }
    
        return $clientes;
    }
    
    public function buscar_recientes($telefono, $cliente_id){

        $query = "SELECT
                        id,
                        `timestamp`,
                        nombre,
                        `address`,
                        email,
                        telefono,
                        web,
                        fecha_envio,
                        response,
                        source_phone,
                        batch_id 
                    FROM
                        clientes 
                    WHERE
                        telefono = ? 
                        AND id != ? 
                    ORDER BY
                        id DESC 
                        LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $telefono, $cliente_id);
        $resultado = $stmt->execute();
        $result = $stmt->get_result();
        $num = $result->num_rows;
        $row = $result->fetch_array(MYSQLI_ASSOC);

        $compararFecha = ($num != 0) ? $this->comparar_fecha($row["timestamp"]) : "";
    
        return json_encode(array(
            "num" => $num,
            "timestamp" => $row["timestamp"] ?? "",
            "id" => $row["id"] ?? "",
            "compararFecha"=>$compararFecha
        ));

    }

    public function comparar_fecha($timestamp){

        $fecha = new DateTime($timestamp);
        $hoy = new DateTime();
        $hace30dias = (clone $hoy)->modify('-30 days');

        if ($fecha < $hace30dias) {
            return "no reciente";
        } else {
            return "reciente";
        }

    }
    
    // Función para formatear la fecha del mensaje
    private function formatearFecha($timestamp) {
        if (!$timestamp) return "";
    
        $fechaMensaje = new DateTime($timestamp);
        $hoy = new DateTime();
        $ayer = new DateTime("yesterday");
    
        if ($fechaMensaje->format('Y-m-d') === $hoy->format('Y-m-d')) {
            return $fechaMensaje->format('H:i');
        } elseif ($fechaMensaje->format('Y-m-d') === $ayer->format('Y-m-d')) {
            return "Yesterday " . $fechaMensaje->format('H:i');
        } else {
            return $fechaMensaje->format('d/m/Y H:i');
        }
    }
       
    public function enviar_plantilla($destination_phone, $source_phone, $nombre, $clienteid){

        $destination_phone = (substr($destination_phone, 0, 3) === '521') ? $destination_phone : '521' . $destination_phone;

        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $destination_phone,
            "type" => "template",
            "template" => [
                "name" => "promoimg1",
                "language" => [
                    "code" => "Es_MX"
                ],
                "components" => [
                    [
                        "type" => "header",
                        "parameters" => [
                            [
                                "type" => "image",
                                "image" => [
                                    "link" => "https://yupii.com.mx:3081/wa3a.jpg"
                                ]
                            ]
                        ]
                    ],
                    [
                        "type" => "body",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $nombre
                            ]
                        ]
                    ],
                    [
                        "type" => "button",
                        "sub_type" => "quick_reply",
                        "index" => "0",
                        "parameters" => [
                            [
                                "type" => "payload",
                                "payload" => "Mas Informacion"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    
        $ch = curl_init("https://partner.gupshup.io/partner/app/52761ce2-fd88-446c-9a5e-fcfe9bf41c21/v3/message");
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'Authorization: sk_5a3b9c4fd7164575af0bd685dc6b2a30',
            'Content-Type: application/json'
        ]);
        
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // Envio de mensaje
        $response = curl_exec($ch);
        
        // if (curl_errno($ch)) {
        //     echo 'Curl error: ' . curl_error($ch);
        // }

        if ($f = fopen($GLOBALS["baseurl"] . "enviar_plantilla" . date('Ymd') . ".txt", "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " payload=" . json_encode($payload) . ", response=" . $response . ", destination_phone=" . $destination_phone . "\r\n");
            fclose($f);
        }
    
        curl_close($ch);
        $json_res = json_decode($response);
        $messageId = ( !empty($json_res->messages[0]->id) )?$json_res->messages[0]->id:"";

        $filename = "wa3a.jpg";
        $user_id = "1";
        $user_name = "IA";
        $message = "Hasta $100,000 para 🚀 crecer tu negocio  🏪 ".$nombre."!<br>Con el Plan Nacional; Pideaky apoya crecimiento de pequeños negocios con préstamos desde 10,000 hasta 100,000 pesos.<br>Tu eliges el plazo desde 8 a 42 semanas.<br>No pedimos anticipos , pide una cita y un asesor te visitará para informarte.";

        $array_message = array("destination_phone"=>$destination_phone, 
                                "message"=>$message, 
                                "response"=>$response, 
                                "source_phone"=>$source_phone, 
                                'user_id'=>$user_id, 
                                'user_name'=>$user_name, 
                                'messageId'=>$messageId,
                                'filename'=>$filename,
                                'file_url'=>"https://yupii.com.mx:3081/wa3a.jpg",
                                'file_type'=>'image/jpeg'
                            );

        $save_message = $this->save_templateFileSent(json_encode($array_message));
        $json_save = json_decode($save_message);

        $messageinsert_id = ($json_save->status == "ok")?$json_save->id:0;

        if( !empty($messageinsert_id)){
            // save last message
            $save_lastmessage = $this->save_lastmessage($destination_phone, $source_phone, $messageinsert_id, "sent", $destination_phone, "file");
            $update_sentMessage = $this->update_tblClientes($clienteid, $messageId);
        }
    
        return json_encode($array_message);
    }

    public function update_tblClientes($clienteid, $messageId){

        $query = "UPDATE clientes SET fecha_envio = CURRENT_TIMESTAMP, response = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $messageId, $clienteid);

        $res = $stmt->execute();
    
        // Registro en archivo de log
        if ($f = fopen($GLOBALS["baseurl"] . "update_tblClientes" . date('Ymd') . ".txt", "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " R=" . ($res ? 'true' : 'false') . ", E=" . $stmt->error . ", Q=" . $query . "\r\n");
            fclose($f);
        }

        if (!$res) {
            return json_encode(array("status" => "false", "msj" => $stmt->error));
        } else {
            return json_encode(array("status" => "ok", "id" => $message_id));
        }
    }

    public function save_templateFileSent($data) {
        $json = json_decode($data);
    
        $this->conn->query("SET NAMES utf8mb4;");
    
        $query = "INSERT INTO customerfiles(
            filename, file_url, file_type, fileorigin, gsid, caption, phone_wa, source_phone, user_id, user_name
        ) VALUES (?, ?, ?, 'sent', ?, ?, ?, ?, ?, ?)";
    
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            return json_encode(array(
                "status" => "false",
                "msj" => $this->conn->error,
                "response" => $json->message
            ));
        }
    
        $stmt->bind_param(
            "sssssssss",
            $json->filename,
            $json->file_url,
            $json->file_type,
            $json->messageId,
            $json->message,
            $json->destination_phone,
            $json->source_phone,
            $json->user_id,
            $json->user_name
        );
    
        $res = $stmt->execute();
    
        // Log
        if ($f = fopen($GLOBALS["baseurl"] . "save_messagesent" . date('Ymd') . ".txt", "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " R=" . json_encode($res) . ", E=" . $stmt->error . ", Q=" . $query . "\r\n");
            fclose($f);
        }
    
        if (!$res) {
            return json_encode(array(
                "status" => "false",
                "msj" => $stmt->error,
                "response" => $json->message
            ));
        } else {
            return json_encode(array(
                "status" => "ok",
                "id" => $this->conn->insert_id,
                "response" => $json->message
            ));
        }
    }
    
    public function save_lastmessage($phone_wa, $source_phone, $message_id, $origin, $profile_name, $type){

        $get_contactphone = $this->get_contactphone($phone_wa, $source_phone);
        $json_contactphone = json_decode($get_contactphone);

        $count = 0;
        // update last message
        if($json_contactphone->num >= 1){
            $contactphone_id = $json_contactphone->id;
            
            $upd_lastmessage = $this->update_lastcontactmessage($phone_wa, $source_phone, $message_id, $origin, $profile_name, $type, $count, $contactphone_id);
        
            $json_res = json_decode($upd_lastmessage);
            
            $msj = ($json_res->status == "ok")? "ok" : $json_res->msj;

            $action = "update";
        }
        else{
            // insert last message
            
            $insert_lastmessage = $this->insert_lastmessage($phone_wa, $source_phone, $message_id, $origin, $profile_name, $type, $count, "");
            
            $json_res = json_decode($insert_lastmessage);
            $contactphone_id = ($json_res->status == "ok")? $json_res->id : 0;
            $msj = ($json_res->status == "ok")? "ok" : $json_res->msj;

            $action = "insert";
        }

        if( !empty($message_id) && !empty($contactphone_id) ){
            
            $upd_phone_id = $this->upd_phoneid($contactphone_id, $message_id, $type);

        }

        if ($f=fopen($GLOBALS["baseurl"]."save_lastmessage" . date('Ymd') . ".txt", "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " phone=" . $phone_wa . ", source_phone=" . $source_phone . ", message_id=" . $message_id . ", origin=" . $origin . ", profile_name=" . $profile_name . ", type=" . $type . ", contactphone_id=" . $contactphone_id . ", msj=" . $msj . ", action=" . $action . "\r\n");
            fclose($f);
        }

        return json_encode(array("phone_id"=>$contactphone_id, "status"=>$msj));

    }

    public function get_contactphone($phone_wa, $source_phone) {
        $query = "SELECT
                    id,
                    `timestamp`,
                    phone,
                    profile_name,
                    message_id,
                    origin,
                    source_phone,
                    message_type,
                    count 
                  FROM phones
                  WHERE phone = ? AND source_phone = ?
                  ORDER BY id DESC LIMIT 1";
    
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            return json_encode(array(
                "num" => 0,
                "error" => $this->conn->error
            ));
        }
    
        $stmt->bind_param("ss", $phone_wa, $source_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $num = $result->num_rows;
        $row = $result->fetch_array(MYSQLI_ASSOC);
    
        return json_encode(array(
            "num" => $num,
            "timestamp" => $row["timestamp"] ?? "",
            "id" => $row["id"] ?? "",
            "count" => $row["count"] ?? ""
        ));
    }
    

    public function update_lastcontactmessage($phone_wa, $source_phone, $message_id, $origin, $profile_name, $type, $count, $contactphone_id) {
        $this->conn->query("SET NAMES utf8mb4;");
    
        $query = "UPDATE phones SET 
                        message_id = ?, 
                        origin = ?, 
                        message_type = ?, 
                        count = ?, 
                        lasttimestamp = CURRENT_TIMESTAMP 
                  WHERE id = ?";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issii", $message_id, $origin, $type, $count, $contactphone_id);
    
        $res = $stmt->execute();
    
        // Registro en archivo de log
        if ($f = fopen($GLOBALS["baseurl"] . "update_lastcontactmessage" . date('Ymd') . ".txt", "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " R=" . ($res ? 'true' : 'false') . ", E=" . $stmt->error . ", Q=" . $query . "\r\n");
            fclose($f);
        }
    
        if (!$res) {
            return json_encode(array("status" => "false", "msj" => $stmt->error));
        } else {
            return json_encode(array("status" => "ok", "id" => $message_id));
        }
    }
    
    public function insert_lastmessage($phone_wa, $source_phone, $message_id, $origin, $profile_name, $type, $count, $timestamp) {
        $this->conn->query("SET NAMES utf8mb4;");
    
        $useCurrentTimestamp = empty($timestamp);
        $query = $useCurrentTimestamp
            ? "INSERT INTO phones(phone, profile_name, message_id, origin, source_phone, message_type, count, lasttimestamp)
               VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)"
            : "INSERT INTO phones(phone, profile_name, message_id, origin, source_phone, message_type, count, lasttimestamp)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
        $stmt = $this->conn->prepare($query);
    
        if ($useCurrentTimestamp) {
            $stmt->bind_param("ssisssi", $phone_wa, $profile_name, $message_id, $origin, $source_phone, $type, $count);
        } else {
            $stmt->bind_param("ssisssis", $phone_wa, $profile_name, $message_id, $origin, $source_phone, $type, $count, $timestamp);
        }
    
        $res = $stmt->execute();
    
        // Registro en log
        if ($f = fopen($GLOBALS["baseurl"] . "insert_lastmessage" . date('Ymd') . ".txt", "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " R=" . ($res ? 'true' : 'false') . ", E=" . $stmt->error . ", Q=" . $query . "\r\n");
            fclose($f);
        }
    
        if (!$res) {
            return json_encode(array("status" => "false", "msj" => $stmt->error));
        } else {
            return json_encode(array("status" => "ok", "id" => $this->conn->insert_id));
        }
    }
    
    public function upd_phoneid($phone_id, $message_id, $type) {
        $this->conn->query("SET NAMES utf8mb4;");
    
        if ($type === "text") {
            $query = "UPDATE sent_messages SET phone_id = ? WHERE id = ?";
        } else if ($type === "file") {
            $query = "UPDATE customerfiles SET phone_id = ? WHERE id = ?";
        } else {
            return json_encode(array("status" => "false", "msj" => "Tipo inválido"));
        }
    
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $phone_id, $message_id);
        $res = $stmt->execute();
    
        // Registro en log
        if ($f = fopen($GLOBALS["baseurl"] . "upd_phoneid" . date('Ymd') . ".txt", "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " R=" . ($res ? 'true' : 'false') . ", E=" . $stmt->error . ", Q=" . $query . ", T=" . $type . "\r\n");
            fclose($f);
        }
    
        if (!$res) {
            return json_encode(array("status" => "false", "msj" => $stmt->error));
        } else {
            return json_encode(array("status" => "ok", "id" => $message_id));
        }
    }

    // Guarda un batch de excel
    public function save_batch($nombreOriginal, $nombreGuardado) {
        
        // Insertar en tabla batches
        $stmt = $this->conn->prepare("INSERT INTO batches (`name`, `filename`) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombreOriginal, $nombreGuardado);
        $res = $stmt->execute();
        $batchId = $stmt->insert_id;

        if (!$res) {
            return json_encode(array("status" => "false", "msj" => $stmt->error));
        } else {
            return json_encode(array("status" => "ok", "id" => $batchId));
        }

    }

    // Obtener la lista de batches con total de registros
    public function obtenerBatches() {
        $sql = "SELECT b.id, b.timestamp, b.name, b.filename, COUNT(c.id) as total_registros
                FROM batches b
                LEFT JOIN clientes c ON c.batch_id = b.id
                GROUP BY b.id
                ORDER BY b.timestamp DESC";

        $result = $this->conn->query($sql);

        $batches = [];
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }

        return $batches;
    }

    
    public function obtenerClientesPorBatch($batch_id) {
        $stmt = $this->conn->prepare("SELECT nombre, CONCAT( IF ( LEFT ( c.telefono, 3 ) = '521', '', '521' ), c.telefono ) as telefono, email, web FROM clientes WHERE batch_id = ?");
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
    
        return $clientes;
    }
    
    public function obtenerBatchesPorFecha($fecha) {
        $stmt = $this->conn->prepare("SELECT id, `timestamp`, `name`, `filename` FROM `batches` WHERE DATE(timestamp) = ?");
        $stmt->bind_param("s", $fecha);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $batches = [];
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }
        return $batches;
    }

    public function obtenerResumenPorBatch($batch_id) {
        $query = "SELECT
                    COUNT(c.id) AS solicitados,
                    SUM(CASE WHEN cf.failed IS NOT NULL THEN 1 ELSE 0 END) AS rechazados,
                    SUM(CASE WHEN cf.sent IS NOT NULL THEN 1 ELSE 0 END) AS enviados,
                    SUM(CASE WHEN cf.delivered IS NOT NULL THEN 1 ELSE 0 END) AS entregados,
                    SUM(CASE WHEN cf.seen IS NOT NULL THEN 1 ELSE 0 END) AS leidos,
                    SUM( CASE WHEN c.replymessage_id IS NOT NULL AND c.replymessage_id != '' AND c.replymessage_id != 0 THEN 1 ELSE 0 END ) AS respondidos
                  FROM clientes c
                  LEFT JOIN customerfiles cf ON cf.gsid = c.response AND c.response != '' AND c.response IS NOT NULL
                  WHERE c.batch_id = ?";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // Devuelve solo un objeto asociativo
    }
    
    public function obtenerConversacionCompleta($dateini, $datefin, $phone, $source_phone) {
        
        $this->conn->query("SET NAMES utf8mb4;");
        
        $mensajes = [];
        
        // Mensajes recibidos
        $queryRecibidos = "SELECT 
                            `timestamp`,
                            message_received AS mensaje,
                            NULL AS archivo,
                            'recibido' AS tipo
                        FROM messageschb
                        WHERE phone_wa = ? AND source_phone = ?
                        AND (`timestamp` BETWEEN ? AND ?)";
        $stmt1 = $this->conn->prepare($queryRecibidos);
        $stmt1->bind_param("ssss", $phone, $source_phone, $dateini, $datefin);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        while ($row = $res1->fetch_assoc()) {
            $mensajes[] = $row;
        }
    
        // Mensajes enviados
        $queryEnviados = "SELECT 
                            `timestamp`,
                            message_sent AS mensaje,
                            NULL AS archivo,
                            'enviado' AS tipo
                        FROM sent_messages
                        WHERE phone = ? AND source_phone = ?
                        AND (`timestamp` BETWEEN ? AND ?)";
        $stmt2 = $this->conn->prepare($queryEnviados);
        $stmt2->bind_param("ssss", $phone, $source_phone, $dateini, $datefin);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) {
            $mensajes[] = $row;
        }
    
        // Archivos (imágenes/videos/etc)
        $queryArchivos = "SELECT 
                            `timestamp`,
                            caption AS mensaje,
                            file_url AS archivo,
                            fileorigin AS tipo
                        FROM customerfiles
                        WHERE phone_wa = ? AND source_phone = ?
                        AND (`timestamp` BETWEEN ? AND ?)";
        $stmt3 = $this->conn->prepare($queryArchivos);
        $stmt3->bind_param("ssss", $phone, $source_phone, $dateini, $datefin);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        while ($row = $res3->fetch_assoc()) {
            // 'fileorigin' tiene 'sent' o 'received'
            $row['tipo'] = ($row['tipo'] === 'sent') ? 'enviado' : 'recibido';
            $mensajes[] = $row;
        }
        
        // Ordenar todos los mensajes por timestamp
        usort($mensajes, function ($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });
        
        return $mensajes;
    }
      
    public function API_send_message($source_phone, $destination_phone, $message){

        // Prepara curl para el envio por API
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.gupshup.io/wa/api/v1/msg',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'channel=whatsapp&source=521'.$source_phone.'&destination='.$destination_phone.'&src.name=ProdPideAky&message=%7B%22type%22%3A%22text%22%2C%22text%22%3A%22'.$message.'%22%7D',
        CURLOPT_HTTPHEADER => array(
            'apikey: gsrppbbcyds5luwtbpcdyrwkonoc8bex',
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        // Guarda log de respuesta
        if ($f=fopen($GLOBALS["baseurl"]."API_send_message" . date('Ymd') . ".txt", "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " M=" . $message . ", source_phone=" . $source_phone . ", destination_phone=" . $destination_phone . ", R=" . $response . "\r\n");
            fclose($f);
        }

        return $response;

    }

    public function save_messagesent($message, $telefono, $source_phone, $response_message, $user_id, $user_name, $gsid) {
        
        $this->conn->query("SET NAMES utf8mb4;");
    
        $query = "INSERT INTO sent_messages (message_sent, phone, source_phone, response, user_id, user_name, gsid)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    
        $stmt = $this->conn->prepare($query);
        
        $stmt->bind_param("ssssiss", $message, $telefono, $source_phone, $response_message, $user_id, $user_name, $gsid);
        $res = $stmt->execute();

        $messageinsert_id = $this->conn->insert_id;

        if( !empty($messageinsert_id)){
            // save last message
            $save_lastmessage = $this->save_lastmessage($telefono, $source_phone, $messageinsert_id, "sent", $telefono, "text");
        }
    
        // Logging
        $logFile = $GLOBALS["baseurl"] . "save_messagesent" . date('Ymd') . ".txt";
        if ($f = fopen($logFile, "a")) {
            fwrite($f, date('Y-m-d H:i:s') . " R=" . ($res ? "1" : "0") . ", E=" . $stmt->error . ", Q=" . $query . "\r\n");
            fclose($f);
        }
    
        if (!$res) {
            return json_encode(array(
                "status" => "false",
                "msj" => $stmt->error
            ));
        } else {
            return json_encode(array(
                "status" => "ok",
                "id" => $this->conn->insert_id
            ));
        }
    }
    
        
}
?>


