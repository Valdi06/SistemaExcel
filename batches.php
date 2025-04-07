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
            <ul id="detalleLista" class="list-group"></ul>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function () {
            $(".batch-item").on("click", function () {
                const batchId = $(this).data("id");
                console.log("🧾 Cargando batch ID:", batchId);

                $.ajax({
                    url: "cargar_batch.php",
                    type: "POST",
                    data: { batch_id: batchId },
                    success: function (response) {
                        const data = JSON.parse(response);
                        $("#detalleLista").empty();

                        if (data.length === 0) {
                            $("#detalleLista").append(`<li class="list-group-item">Sin registros para este batch.</li>`);
                            return;
                        }

                        data.forEach(item => {
                            $("#detalleLista").append(`
                                <li class="list-group-item">
                                    <strong>${item.nombre}</strong> - ${item.telefono} <br>
                                    📧 ${item.email} | 🌐 ${item.web}
                                </li>
                            `);
                        });
                    },
                    error: function (xhr, status, error) {
                        console.error("❌ Error al cargar el batch:", error);
                    }
                });
            });
        });
    </script>
</body>
</html>

