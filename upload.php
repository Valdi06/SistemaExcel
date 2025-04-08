<?php
require 'vendor/autoload.php';
require_once 'Cliente.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$cliente = new Cliente();
$telefonosProcesados = [];
$logData = "Importación de datos - " . date("Y-m-d H:i:s") . "\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    if ($_FILES['excel_file']['error'] == 0) {

        // Variables para archivo
        $nombreOriginal = $_FILES['excel_file']['name'];
        $timestamp = date('Ymd_His');
        $nombreGuardado = $timestamp . '_' . $nombreOriginal;
        $rutaDestino = "./batches/$nombreGuardado";

        // Crear carpeta si no existe
        if (!file_exists("./batches")) {
            mkdir("./batches", 0777, true);
        }

        // Guardar el archivo subido
        copy($_FILES['excel_file']['tmp_name'], $rutaDestino);

        $res_savebatch = $cliente->save_batch($nombreOriginal, $nombreGuardado);
        $json_batch = json_decode($res_savebatch);
        $batch_id = ($json_batch->status == "ok")?$json_batch->id : 0;

        // $filePath = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($rutaDestino);
        $worksheet = $spreadsheet->getActiveSheet();

        // Obtener el source_phone desde el formulario
        $template = isset($_POST['template']) ? $_POST['template'] : '';
        $source_phone = isset($_POST['source_phone']) ? $_POST['source_phone'] : '';

        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            if (!empty($rowData[0])) {
                $telefono = $rowData[0];
                $nombre   = $rowData[1];
                $address  = $rowData[2];
                $email    = $rowData[3];
                $web      = $rowData[4];

                $telefono = ( strpos($telefono, '521') !== false) ? $telefono : "521".$telefono;

                if (in_array($telefono, $telefonosProcesados) || $cliente->telefonoEnviadoHoy($telefono)) {
                    continue;
                }

                $fecha_envio = date("Y-m-d H:i:s");
                $response = "";

                if ($cliente->guardarCliente($nombre, $address, $email, $web, $telefono, $response, $source_phone, $batch_id)) {
                    $telefonosProcesados[] = $telefono;

                    // $res_template = $cliente->enviar_plantilla($telefono, $source_phone);

                    // Registrar los datos en el log
                    $logData .= "Nombre: $nombre, address: $address, email: $email, web: $web, Teléfono: $telefono, Source phone: $source_phone, Plantilla: $template\n";
                }
            }
        }
    }
}

// Guardar el log en un archivo
if (!empty($telefonosProcesados)) {
    $file = fopen("./logs/log_importacion.txt", "a"); // "a" para agregar sin sobrescribir
    fwrite($file, $logData . "\n--------------------------------------\n");
    fclose($file);
}

// Devolver los datos actualizados después de la importación con mensajes, fecha e iconos
echo json_encode($cliente->obtenerClientesConMensajes($batch_id));
?>