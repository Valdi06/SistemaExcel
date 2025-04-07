<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="container mt-4">
    <h2 class="mb-3">Subir archivo Excel</h2>
    
    <form id="uploadForm" enctype="multipart/form-data">

        <div class="row">
            <div class="col-4">
                <div class="form-group">
                    <label for="">Elegir archivo</label>
                    <input type="file" name="excel_file" id="excel_file" class="form-control mb-3">
                </div>
            </div>

            <div class="col-4">
                <div class="form-group">
                    <label for="">Plantilla</label>
                    <select name="template" id="template" class="form-select">
                        <!-- <option value="0">Seleccione...</option> -->
                        <option value="1" >Promocion Imagen 1</option>
                    </select>
                </div>
            </div>

            <div class="col-4">
                <div class="form-group">
                    <label for="">Número</label>
                    <select name="source_phone" id="source_phone" class="form-select">
                        <!-- <option value="8183397869">8183397869</option> -->
                        <option value="8116031152">8116031152</option>
                        <!-- <option value="8117960227">8117960227</option> -->
                        <!-- <option value="8113134705">8113134705</option> -->
                        <!-- <option value="8125387659">8125387659</option> -->
                    </select>
                </div>
            </div>

        </div>


        <button type="submit" class="btn btn-primary">Importar Excel</button>
        <a href="./template/plantilla.xlsx" download class="btn btn-warning" style="float: right;">Descargar Plantilla</a>
    </form>

    <hr>

    <div class="text-center mt-4">
        <a href="batches.php" target="_blank" class="btn btn-primary">Ver Lista de Batches</a>
    </div>
    <!-- Tabs para "Todos", "Finalizado" y "No Enviado" -->
    <ul class="nav nav-tabs" id="resultTabs">
        <li class="nav-item">
            <a class="nav-link active" id="todos-tab" data-bs-toggle="tab" href="#todos">Todos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="finalizado-tab" data-bs-toggle="tab" href="#finalizado">Activos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="noenviado-tab" data-bs-toggle="tab" href="#noenviado">Rechazados</a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="todos">
            <div class="lista-wrapper">
                <ul id="todosList" class="list-group"></ul>
            </div>
            <div class="text-center mt-2">
                <button type="button" id="btnTodosAccion" class="btn btn-primary">
                    <i class="fa fa-spinner fa-pulse fa-lg fa-fw cargaS2" style="display:none"></i>
                    <i class="fab fa-whatsapp iconoS2"></i>
                    Enviar
                </button>
            </div>
        </div>
        <div class="tab-pane fade" id="finalizado">
            <div class="lista-wrapper">
                <ul id="finalizadoList" class="list-group"></ul>
            </div>
            <div class="text-center mt-2">
                <button type="button" id="btnFinalizadoAccion" class="btn btn-primary">
                    <i class="fa fa-spinner fa-pulse fa-lg fa-fw cargaS2" style="display:none"></i>
                    <i class="fab fa-whatsapp iconoS2"></i>
                    Enviar
                </button>
            </div>
        </div>
        <div class="tab-pane fade" id="noenviado">
            <div class="lista-wrapper">
                <ul id="noEnviadoList" class="list-group"></ul>
            </div>
            <div class="text-center mt-2">
                <button type="button" id="btnNoEnviadosAccion" class="btn btn-primary">
                    <i class="fa fa-spinner fa-pulse fa-lg fa-fw cargaS2" style="display:none"></i>
                    <i class="fab fa-whatsapp iconoS2"></i>
                    Enviar
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>


