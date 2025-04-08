$(document).ready(function () {
    // Cargar datos al cargar la página
    // cargarDatos();

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

    const hoy = new Date().toISOString().split("T")[0];
    $("#fechaFiltro").val(hoy).trigger("change");
    
});

// Función para cargar los datos al inicio
function cargarDatos(batch_id) {
    $.ajax({
        url: "cargar_datos.php",
        type: "POST",
        data: {'batch_id':batch_id},
        success: function (response) {
            actualizarListas(response);
            cargarResumen(batch_id);
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
        // let esReciente = (item.reciente == "reciente") ? true : false;
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
        let txtEnvio = item.response ? "Envio ID: " + item.response : "";

        let disablechk = item.fecha_envio ? "disabled" : "";

        let listItem = `
                <li id="li_${item.id}" class="list-group-item d-flex align-items-center justify-content-between cliente-item ${fondoClase}" data-telefono="${item.telefono}" data-nombre="${item.nombre}">
                    <div class="d-flex align-items-start gap-2">
                        <input class="form-check-input mt-1" type="checkbox" id="chk_${item.id}" value="${item.telefono}" data-clienteid="${item.id}" data-nombre="${item.nombre}" ${checked} ${disablechk}>
                        <div>
                            <strong>${item.nombre} (${item.telefono})</strong> <small><em>(${item.address})</em></small>
                            <br>
                            <small><em>${mensaje}</em></small>
                            <br>
                            <small id="small_${item.id}">${txtEnvio}</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">${mensajeFecha}</small>
                        <br>
                        ${estadoIcono}
                    </div>
                </li>`;

        $("#todosList").append(listItem);
        if (item.mensaje_estado != 'failed') {
            $("#finalizadoList").append(listItem);
        } else {
            $("#noEnviadoList").append(listItem);
        }
    });
}

// Función para obtener el icono basado en el estado del mensaje

function obtenerIconoMensaje(item) {
    let icon = "";

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
                $("#li_"+clienteid).addClass("bg-warning-subtle");
                $("#chk_"+clienteid).prop('checked', false);
                $("#chk_"+clienteid).attr('disabled','disabled');
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

$(".batch-item").on("click", function () {
    let batchId = $(this).data('id');
    console.log(`🔍 Cargando registros del batch ID: ${batchId}`);

    cargarDatos(batchId);
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

$("#fechaFiltro").on("change", function () {
    const fecha = $(this).val();
    if (fecha) {
        $.post("obtener_batches_fecha.php", { fecha: fecha }, function (response) {
            let data = JSON.parse(response);
            let options = `<option value="">-- Selecciona un batch --</option>`;
            data.forEach(batch => {
                options += `<option value="${batch.id}">${batch.timestamp} - ${batch.name}</option>`;
            });
            $("#batchSelect").html(options);
        });
    }
});

// Al seleccionar un batch, obtener los registros de ese batch_id
$("#batchSelect").on("change", function () {
    const batch_id = $(this).val();
    if (batch_id) {
        cargarDatos(batch_id);
    } 
});

function cargarResumen(batch_id) {
    $.ajax({
        url: "obtener_resumen.php",
        method: "POST",
        data: { batch_id: batch_id },
        dataType: "json",
        success: function (data) {
            console.log(data);
            if (data) {
                $("#resumen_solicitados").text(data.solicitados);
                $("#resumen_rechazados").text(data.rechazados);
                $("#resumen_enviados").text(data.enviados);
                $("#resumen_entregados").text(data.entregados);
                $("#resumen_leidos").text(data.leidos);
                $("#resumen_respondidos").text(data.respondidos);

                // (Opcional) Actualizar gráfica si tienes una
                actualizarResumen(data);
            }
        },
        error: function (xhr, status, error) {
            console.error("Error al cargar resumen:", error);
        }
    });
}

let resumenChart; // Variable global para que podamos actualizarla

function actualizarResumen(resumen) {
    const {
        solicitados = 0,
        rechazados = 0,
        enviados = 0,
        entregados = 0,
        leidos = 0,
        respondidos = 0
    } = resumen;

    // Actualiza valores en la lista
    $(".solicitados").css("background-color", "#007bff");
    $(".rechazados").css("background-color", "#dc3545");
    $(".enviados").css("background-color", "#17a2b8");
    $(".entregados").css("background-color", "#28a745");
    $(".leidos").css("background-color", "#ffc107");
    $(".respondidos").css("background-color", "#6f42c1");

    const labels = ["Solicitados", "Rechazados", "Enviados", "Entregados", "Leídos", "Respondidos"];
    const data = [solicitados, rechazados, enviados, entregados, leidos, respondidos];
    const colors = ["#007bff", "#dc3545", "#17a2b8", "#28a745", "#ffc107", "#6f42c1"];

    const ctx = document.getElementById("resumenGrafica").getContext("2d");

    if (resumenChart) {
        resumenChart.data.datasets[0].data = data;
        resumenChart.update();
    } else {
        resumenChart = new Chart(ctx, {
            type: "pie",
            data: {
                labels: labels,
                datasets: [{
                    label: "Resumen",
                    data: data,
                    backgroundColor: colors
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: "index", intersect: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
}

let telefonoSeleccionado = "";
let nombreSeleccionado = "";

// Evento al hacer clic en un cliente
$(document).on("click", ".cliente-item", function () {
    telefonoSeleccionado = $(this).data("telefono");
    nombreSeleccionado = $(this).data("nombre");

    let source_phone = $("#source_phone").val();

    $("#chatTelefono").text(`${nombreSeleccionado} (${telefonoSeleccionado})`);
    $("#mensajeInput").val("");
    $("#chatMensajes").html(""); // Limpia el historial por ahora

    $("#chatModal").modal("show");
    cargarConversacion(telefonoSeleccionado, source_phone);
});

// Evento al dar clic en el botón Enviar
$("#btnEnviarMensaje").click(function () {
    const mensaje = $("#mensajeInput").val().trim();
    if (mensaje !== "") {
        console.log(`Mensaje para ${telefonoSeleccionado}: ${mensaje}`);

        // Agrega el mensaje en la vista del chat (lado derecho)
        $("#chatMensajes").append(`<div class="mensaje-enviado">${mensaje}</div>`);
        $("#mensajeInput").val("").focus();
        scrollChatToBottom();
    }
});

$("#mensajeInput").on("keypress", function (e) {
    if (e.which === 13 && !e.shiftKey) { // Enter sin Shift
        e.preventDefault(); // Evita salto de línea
        $("#btnEnviarMensaje").click(); // Dispara el botón
    }
});

function scrollChatToBottom() {
    const contenedor = $("#chatMensajes");
    contenedor.scrollTop(contenedor.prop("scrollHeight"));
}

function cargarConversacion(phone, source_phone) {
    let dateSelect = $("#fechaFiltro").val();
    const hoy = new Date(dateSelect).toISOString().split("T")[0];
    const dateini = `${hoy} 00:00:00`;
    const datefin = `${hoy} 23:59:59`;

    $.ajax({
        url: 'get_conversacion.php', // URL del archivo PHP
        type: 'POST',
        data: {
            dateini: dateini,
            datefin: datefin,
            phone: phone,
            source_phone: source_phone
        },
        success: function (data) {
            const mensajes = JSON.parse(data);  // Parsear la respuesta JSON
            const contenedor = $("#chatMensajes");
            contenedor.empty();  // Limpiar el contenedor antes de agregar los nuevos mensajes

            mensajes.forEach(msg => {
                const lado = msg.tipo === "enviado" ? "text-end" : "text-start";
                const clase = msg.tipo === "enviado" ? "mensaje-enviado" : "bg-light";
                let contenido = "";

                // Si el mensaje tiene archivo, procesarlo
                if (msg.archivo) {
                    const ext = msg.archivo.split('.').pop().toLowerCase();
                    if (["jpg", "jpeg", "png", "gif"].includes(ext)) {
                        contenido += `<img src="${msg.archivo}" class="img-thumbnail" style="max-width:250px;"><br>`;
                    } else {
                        contenido += `<a href="${msg.archivo}" target="_blank">📎 Ver archivo</a><br>`;
                    }
                }

                contenido += msg.mensaje || "";  // Agregar el texto del mensaje

                // Añadir el mensaje al contenedor
                contenedor.append(`
                    <div class="mb-2 ${lado}">
                        <div class="p-2 rounded ${clase}" style="display: inline-block; max-width: 60%;">
                            ${contenido}
                        </div>
                    </div>
                `);
            });

            // Hacer scroll hacia el último mensaje
            contenedor.scrollTop(contenedor[0].scrollHeight);
        },
        error: function(xhr, status, error) {
            console.error("Error al obtener la conversación:", error);  // Manejo de errores
        }
    });
}
