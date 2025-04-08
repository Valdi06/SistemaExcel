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

    <div class="row">

        <div class="col-7">

            <div class="row">
                <div class="col-6">
                    <h2 class="mb-3">Subir archivo Excel</h2>
                </div>
                <div class="col-3">
                    <a href="./template/plantilla.xlsx" download class="btn btn-warning">Descargar Plantilla</a>
                </div>
                <div class="col-3">
                    <a href="batches.php" target="_blank" class="btn btn-primary">Ver Lista de Batches</a>
                </div>
            </div>

            <form id="uploadForm" enctype="multipart/form-data">

                <div class="row mb-2">
                    <div class="col-md-4">
                        <label for="fechaFiltro">Selecciona una fecha:</label>
                        <input type="date" id="fechaFiltro" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label for="batchSelect">Selecciona un batch:</label>
                        <select id="batchSelect" class="form-select">
                            <option value="">-- Selecciona un batch --</option>
                        </select>
                    </div>
                </div>

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
            </form>

        </div>

        <div class="col-5">
            <!-- Resumen -->
             <div class="row">
                <div class="col-6">
                    <!-- Gráfica -->
                    <div class="card-body">
                        <h5 class="card-title">Resumen de Envíos</h5>
                        <!-- <ul class="list-group mb-3" id="resumenLista">
                            <li class="list-group-item d-flex justify-content-between"><span>📨 Solicitados</span> <span id="resSolicitados">0</span></li>
                            <li class="list-group-item d-flex justify-content-between"><span>❌ Rechazados</span> <span id="resRechazados">0</span></li>
                            <li class="list-group-item d-flex justify-content-between"><span>📤 Enviados</span> <span id="resEnviados">0</span></li>
                            <li class="list-group-item d-flex justify-content-between"><span>📬 Entregados</span> <span id="resEntregados">0</span></li>
                            <li class="list-group-item d-flex justify-content-between"><span>👁️ Leídos</span> <span id="resLeidos">0</span></li>
                            <li class="list-group-item d-flex justify-content-between"><span>💬 Respondidos</span> <span id="resRespondidos">0</span></li>
                        </ul> -->
                        <canvas id="resumenGrafica" height="250"></canvas>
                    </div>

                </div>
                <div class="col-6">
                    <h4>Resumen</h4>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="solicitados" >Solicitados</th>
                                <td class="solicitados"  id="resumen_solicitados"></td>
                            </tr>
                            <tr>
                                <th class="rechazados" >Rechazados</th>
                                <td class="rechazados"  id="resumen_rechazados"></td>
                            </tr>
                            <tr>
                                <th class="enviados" >Enviados</th>
                                <td class="enviados"  id="resumen_enviados"></td>
                            </tr>
                            <tr>
                                <th class="entregados" >Entregados</th>
                                <td class="entregados"  id="resumen_entregados"></td>
                            </tr>
                            <tr>
                                <th class="leidos" >Leídos</th>
                                <td class="leidos"  id="resumen_leidos"></td>
                            </tr>
                            <tr>
                                <th class="respondidos" >Respondidos</th>
                                <td class="respondidos"  id="resumen_respondidos"></td>
                            </tr>
                        </tbody>
                    </table>

                </div>
             </div>
        </div>


    </div>

    <hr>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>