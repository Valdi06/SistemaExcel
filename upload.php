<?php
require 'vendor/autoload.php';
require_once 'Cliente.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$cliente = new Cliente();
$telefonosProcesados = [];
$logData = "Importación de datos - " . date("Y-m-d H:i:s") . "\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    if ($_FILES['excel_file']['error'] == 0) {
        $filePath = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($filePath);
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
                $nombre = $rowData[0];
                $paterno = $rowData[1];
                $materno = $rowData[2];
                $telefono = $rowData[3];

                if (in_array($telefono, $telefonosProcesados) || $cliente->telefonoEnviadoHoy($telefono)) {
                    continue;
                }

                $fecha_envio = date("Y-m-d H:i:s");
                $response = rand(0, 1) ? "Enviado" : "No Enviado";

                if ($cliente->guardarCliente($nombre, $paterno, $materno, $telefono, $fecha_envio, $response, $source_phone)) {
                    $telefonosProcesados[] = $telefono;

                    $res_template = $cliente->enviar_plantilla($telefono, $source_phone);

                    // Registrar los datos en el log
                    $logData .= "Nombre: $nombre, Paterno: $paterno, Materno: $materno, Teléfono: $telefono, Source phone: $source_phone, Plantilla: $template\n";
                }
            }
        }
    }
}

// Guardar el log en un archivo
if (!empty($telefonosProcesados)) {
    $file = fopen("log_importacion.txt", "a"); // "a" para agregar sin sobrescribir
    fwrite($file, $logData . "\n--------------------------------------\n");
    fclose($file);
}

// Devolver los datos actualizados después de la importación con mensajes, fecha e iconos
echo json_encode($cliente->obtenerClientesConMensajes());
?>