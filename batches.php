<?php
require_once 'Cliente.php';
$cliente = new Cliente();
$batches = $cliente->obtenerBatches(); // Asegúrate de tener esta función en Cliente.php
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Batches Subidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        #batchList {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <h2 class="mb-4">📦 Archivos Excel Subidos</h2>
        
        <ul id="batchList" class="list-group">
            <?php foreach ($batches as $batch): ?>
                <li class="list-group-item batch-item" data-id="<?= $batch['id'] ?>">
                    🗂️ <?= $batch['name'] ?> - <?= date("Y-m-d H:i", strtotime($batch['timestamp'])) ?> - <?= $batch['total_registros'] ?> registros
                </li>
            <?php endforeach; ?>
        </ul>

        <div id="batchDetalles" class="mt-4">
            <h4>📋 Detalles del Batch</h4>
            <div class="lista-wrapper">
                <ul id="todosList" class="list-group"></ul>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="script.js"></script>
    <script>
        
    </script>
</body>
</html>

