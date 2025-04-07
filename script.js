$(document).ready(function () {
    // Cargar datos al cargar la página
    cargarDatos();

    // Manejar la carga del archivo Excel
    $("#uploadForm").on("submit", function (e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let sourcePhone = $("#source_phone").val(); // Obtener valor del select
        let template = $("#template").val(); // Obtener valor del select
        formData.append("source_phone", sourcePhone); // Agregar al formData
        formData.append("template", template); // Agregar al formData

        $.ajax({
            url: "upload.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                actualizarListas(response);

                $("#uploadForm")[0].reset();
            }
        });
    });
});

// Función para cargar los datos al inicio
function cargarDatos() {
    $.ajax({
        url: "cargar_datos.php",
        type: "GET",
        success: function (response) {
            actualizarListas(response);
        }
    });
}

function actualizarListas(response) {
    let data = JSON.parse(response);
    $("#todosList").empty();
    $("#finalizadoList").empty();
    $("#noEnviadoList").empty();

    data.forEach(item => {
        let mensaje = item.ultimo_mensaje ? item.ultimo_mensaje : "Sin mensajes";
        let mensajeFecha = item.mensaje_fecha ? item.mensaje_fecha : "";
        let estadoIcono = obtenerIconoMensaje(item);

        // console.log(item);
        // Validar si está dentro de los últimos 30 días
        let esReciente = false;
        if (item.lasttimestamp) {
            let fechaMensaje = new Date(item.lasttimestamp);
            let ahora = new Date();
            let hace30Dias = new Date();
            hace30Dias.setDate(ahora.getDate() - 30);
            esReciente = fechaMensaje > hace30Dias;
        }
        

        let fondoClase = esReciente ? "bg-warning-subtle" : "";
        let checked = esReciente ? "" : "checked";

        let listItem = `
                <li class="list-group-item d-flex align-items-center justify-content-between ${fondoClase}">
                    <div class="d-flex align-items-start gap-2">
                        <input class="form-check-input mt-1" type="checkbox" value="${item.telefono}" data-clienteid="${item.id}" data-nombre="${item.nombre}" ${checked}>
                        <div>
                            <strong>${item.nombre} (${item.telefono})</strong>
                            <br>
                            <small><em>${item.address}</em></small>
                            <br>
                            <small><em>${mensaje}</em></small>
                            <br>
                            <small id="small_${item.id}"></small>
                        </div>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">${mensajeFecha}</small>
                        <br>
                        ${estadoIcono}
                    </div>
                </li>`;

        $("#todosList").append(listItem);
        if (!esReciente) {
            $("#finalizadoList").append(listItem);
        } else {
            $("#noEnviadoList").append(listItem);
        }
    });
}

// Función para obtener el icono basado en el estado del mensaje

function obtenerIconoMensaje(item) {
    let icon = "";

    if (item.origin === "sent") {
        if (item.mensaje_estado == 'queued') {
            icon = `<i class="fas fa-clock"></i>`; // Reloj (En cola)
        } else if (item.mensaje_estado == 'failed') {
            icon = `<i class="fas fa-times ctimes"></i>`; // Equís roja (Fallido)
        } else if (item.mensaje_estado == 'sent') {
            icon = `<i class="fas fa-check"></i>`; // Check negro (Enviado)
        } else if (item.mensaje_estado == 'delivered') {
            icon = `<i class="fas fa-check mcheck"></i><i class="fas fa-check"></i>`; // Doble check negro (Entregado)
        } else if (item.mensaje_estado == 'seen') {
            icon = `<i class="fas fa-check checkread mcheck"></i><i class="fas fa-check checkread"></i>`; // Doble check celeste (Visto)
        }
    }

    return `<span class="status-icon">${icon}</span>`;
}

// Función genérica para mostrar seleccionados de una lista
function send_template(listaId) {

    $("#btnTodosAccion, #btnFinalizadoAccion, #btnNoEnviadosAccion").attr('disabled','disabled');

    $(`#${listaId} input[type="checkbox"]:checked`).each(function () {
        let telefono = $(this).val();
        let nombre = $(this).data("nombre");
        let clienteid = $(this).data("clienteid");
        let template = $("#template").val();
        let source_phone = $("#source_phone").val();
        
        console.log(`📞 ${telefono} - 👤 ${nombre}`);

        $.ajax({
            url: "./send_template.php",
            type: "POST",
            dataType: 'json',
            encode : true,
            data:{'telefono':telefono,
                'nombre':nombre, 
                'template':template, 
                'source_phone':source_phone,
                'clienteid':clienteid
            },
            success: function(res){

                console.log(res);
                $("#small_"+clienteid).html("Envio ID: " + res.messageId);
                
            },
            error: function(xhr, status, error){
                console.error("Error en AJAX:", xhr.responseText || error);
            }
        });

    });
}

// Eventos para los botones
$("#btnTodosAccion").click(() => {
    console.log("✔️ Elementos seleccionados en TODOS:");
    send_template("todosList");
});

$("#btnFinalizadoAccion").click(() => {
    console.log("✔️ Elementos seleccionados en FINALIZADO:");
    send_template("finalizadoList");
});

$("#btnNoEnviadosAccion").click(() => {
    console.log("✔️ Elementos seleccionados en NO ENVIADO:");
    send_template("noEnviadoList");
});

$(document).on('click', '.batch-item', function () {
    let batchId = $(this).data('id');
    console.log(`🔍 Cargando registros del batch ID: ${batchId}`);

    $.ajax({
        url: 'get_batch_details.php',
        type: 'POST',
        dataType: 'json',
        data: { batch_id: batchId },
        success: function (data) {
            mostrarRegistrosDelBatch(data);
        },
        error: function (xhr) {
            console.error("❌ Error al cargar los registros del batch:", xhr.responseText);
        }
    });
});

function mostrarRegistrosDelBatch(registros) {
    let contenedor = $("#batchDetalles");
    contenedor.empty();

    if (registros.length === 0) {
        contenedor.html("<p>No se encontraron registros.</p>");
        return;
    }

    registros.forEach(reg => {
        contenedor.append(`
            <div class="card mb-2 p-2">
                <strong>${reg.nombre} ${reg.paterno} ${reg.materno}</strong><br>
                📞 ${reg.telefono}<br>
                📧 ${reg.email} | 🌐 ${reg.web}<br>
                🏠 ${reg.address}<br>
                🕒 ${reg.fecha_envio}
            </div>
        `);
    });
}
