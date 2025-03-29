<?php
require_once 'config.php'; // Incluir conexión

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
    public function guardarCliente($nombre, $paterno, $materno, $telefono, $fecha_envio, $response, $source_phone) {
        // Evitar duplicados en el mismo archivo Excel
        if (in_array($telefono, $this->telefonosProcesados)) {
            return false; // Ya se procesó en este archivo, no lo guardamos ni mostramos
        }

        // Evitar duplicados exactos en la base de datos
        if ($this->telefonoEnviadoHoy($telefono)) {
            return false; // Ya existe hoy con cualquier hora
        }

        // Insertar el cliente
        $query = "INSERT INTO clientes (nombre, paterno, materno, telefono, fecha_envio, response, source_phone) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssss", $nombre, $paterno, $materno, $telefono, $fecha_envio, $response, $source_phone);
        $resultado = $stmt->execute();

        if ($resultado) {
            $this->telefonosProcesados[] = $telefono; // Registrar el teléfono para evitar duplicados en el mismo archivo
        }

        return $resultado;
    }

    public function obtenerClientes($filtro = "todos") {
        $query = "SELECT id, nombre, paterno, materno, telefono, fecha_envio, response FROM clientes";
    
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

    public function obtenerClientesConMensajes() {
        $query = "
            SELECT
                c.id,
                c.nombre,
                c.paterno,
                c.materno,
                c.telefono,
                c.fecha_envio,
                c.response,
                c.source_phone,
                p.origin,
                COALESCE(
                    (SELECT message_received FROM messageschb WHERE messageschb.id = p.message_id AND p.origin = 'received' AND p.message_type = 'text'),
                    (SELECT message_sent FROM sent_messages WHERE sent_messages.id = p.message_id AND p.origin = 'sent' AND p.message_type = 'text'),
                    (SELECT '📎 Archivo Adjunto' FROM customerfiles WHERE customerfiles.id = p.message_id AND p.message_type = 'file'),
                    'Sin mensajes'
                ) AS ultimo_mensaje,
                p.lasttimestamp AS mensaje_fecha,
    
                -- Determinar el estado del mensaje basado en las columnas
                CASE
                    WHEN p.origin = 'sent' AND p.message_type = 'text' THEN
                        CASE
                            WHEN sm.seen IS NOT NULL THEN 'seen'  -- Visto ✔✔🔵 (Prioridad más alta)
                            WHEN sm.delivered IS NOT NULL THEN 'delivered'  -- Entregado ✔✔
                            WHEN sm.sent IS NOT NULL THEN 'sent'  -- Enviado ✔
                            WHEN sm.failed IS NOT NULL THEN 'failed'  -- Fallido ❌
                            WHEN sm.queued IS NOT NULL THEN 'queued'  -- En cola ⏳ (Menor prioridad)
                            ELSE NULL
                        END
                    WHEN p.origin = 'sent' AND p.message_type = 'file' THEN
                        CASE
                            WHEN cf.seen IS NOT NULL THEN 'seen' 
                            WHEN cf.delivered IS NOT NULL THEN 'delivered' 
                            WHEN cf.sent IS NOT NULL THEN 'sent' 
                            WHEN cf.failed IS NOT NULL THEN 'failed' 
                            WHEN cf.queued IS NOT NULL THEN 'queued' 
                            ELSE NULL
                        END
                    ELSE NULL
                END AS mensaje_estado
    
            FROM clientes c
            LEFT JOIN phones p ON c.telefono = p.phone AND c.source_phone = p.source_phone
            LEFT JOIN sent_messages sm ON sm.id = p.message_id AND p.origin = 'sent' AND p.message_type = 'text'
            LEFT JOIN customerfiles cf ON cf.id = p.message_id AND p.origin = 'sent' AND p.message_type = 'file'
            ORDER BY p.lasttimestamp DESC
        ";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            // Formatear la fecha del mensaje
            $row['mensaje_fecha'] = $this->formatearFecha($row['mensaje_fecha']);
            $clientes[] = $row;
        }
    
        return $clientes;
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
       
    
}
?>


