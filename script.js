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

// Función para actualizar las listas en la interfaz
function actualizarListas(response) {
    let data = JSON.parse(response);
    $("#todosList").empty();
    $("#finalizadoList").empty();
    $("#noEnviadoList").empty();

    data.forEach(item => {
        // console.log(item);
        let mensaje = item.ultimo_mensaje ? item.ultimo_mensaje : "Sin mensajes";
        let mensajeFecha = item.mensaje_fecha ? item.mensaje_fecha : "";
        let estadoIcono = obtenerIconoMensaje(item);

        let listItem = `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>${item.nombre} ${item.paterno} ${item.materno} (${item.telefono})</strong>
                    <br><small><em>${mensaje}</em></small>
                </div>
                <div class="text-end">
                    <small class="text-muted">${mensajeFecha}</small>
                    <br>
                    ${estadoIcono}
                </div>
            </li>`;

        $("#todosList").append(listItem);
        if (item.response === "Enviado") {
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