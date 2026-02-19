<?php
// Este archivo forma parte de Moodle - http://moodle.org/
//
// Moodle es software libre: usted puede redistribuirlo y/o modificarlo
// bajo los términos de la Licencia Pública General GNU publicada por
// la Free Software Foundation, ya sea la versión 3 de la licencia, o
// (a su elección) cualquier versión posterior.
//
// Moodle se distribuye con la esperanza de que sea útil,
// pero SIN NINGUNA GARANTÍA; incluso sin la garantía implícita de
// COMERCIABILIDAD o APTITUD PARA UN PROPÓSITO PARTICULAR. Vea la
// Licencia Pública General GNU para más detalles.
//
// Debería haber recibido una copia de la Licencia Pública General GNU
// junto con este programa. En caso contrario, consulte <https://www.gnu.org/licenses/>.

$string['pluginname'] = 'Firmas digitales';
$string['privacy:metadata'] = 'El plugin Local Firma almacena metadatos de plantillas, firmas y recordatorios para cumplir con los requisitos de auditoría.';
$string['privacy:metadata:templates'] = 'Metadatos de plantillas almacenados por curso.';
$string['privacy:metadata:templates:courseid'] = 'Identificador del curso dueño de la plantilla.';
$string['privacy:metadata:templates:name'] = 'Nombre de la plantilla definido por los docentes.';
$string['privacy:metadata:signatures'] = 'Evidencias de firmas por usuario.';
$string['privacy:metadata:signatures:userid'] = 'Usuario que firmó el documento.';
$string['privacy:metadata:signatures:signedpdfid'] = 'Referencia al archivo PDF firmado.';
$string['privacy:metadata:signatures:ip'] = 'Dirección IP capturada durante la firma.';
$string['privacy:metadata:signatures:useragent'] = 'Cadena user agent capturada durante la firma.';
$string['privacy:metadata:signatures:signaturehash'] = 'Hash de los trazos de la firma manuscrita.';
$string['privacy:metadata:signatures:pdfhash'] = 'Hash del PDF finalizado.';
$string['privacy:metadata:signatures:token'] = 'Token de verificación incrustado en el código QR.';
$string['privacy:metadata:reminders'] = 'Notificaciones de recordatorio enviadas a los usuarios.';
$string['privacy:metadata:reminders:signatureid'] = 'Firma que originó el recordatorio.';
$string['privacy:metadata:reminders:sentat'] = 'Marca de tiempo en la que se envió el recordatorio.';

$string['firma:managetemplates'] = 'Gestionar plantillas de firmas';
$string['firma:viewreports'] = 'Ver reportes de firmas';
$string['firma:sign'] = 'Firmar documentos asignados';
$string['firma:downloadall'] = 'Descargar todos los documentos firmados';

$string['settings_maxpdfpages'] = 'Máximo de páginas por PDF';
$string['settings_maxpdfpages_desc'] = 'Límite de seguridad para las plantillas PDF subidas por el docente (número de páginas).';
$string['settings_reminderinterval'] = 'Intervalo de recordatorios';
$string['settings_reminderinterval_desc'] = 'Tiempo entre notificaciones para firmas pendientes.';
$string['settings_enableqr'] = 'Habilitar verificación con QR';
$string['settings_enableqr_desc'] = 'Cuando está habilitado, cada PDF firmado incluye un código QR con enlace al verificador.';

$string['manage_templates'] = 'Gestionar plantillas';
$string['addtemplate'] = 'Agregar plantilla';
$string['manageversions'] = 'Gestionar versiones';
$string['signatures'] = 'Firmas';
$string['templates'] = 'Plantillas';
$string['versions'] = 'Versiones';
$string['actions'] = 'Acciones';
$string['status_active'] = 'Activa';
$string['status_inactive'] = 'Inactiva';
$string['status_signed'] = 'Firmado';
$string['status_pending'] = 'Pendiente';
$string['status_revoked'] = 'Revocado';
$string['status_draft'] = 'Borrador';
$string['event_template_updated'] = 'Plantilla actualizada';
$string['event_signature_completed'] = 'Documento firmado';

$string['form_name'] = 'Nombre visible';
$string['form_description'] = 'Descripción';
$string['form_section'] = 'Sección asociada';
$string['form_section_none'] = 'Curso completo';
$string['form_type'] = 'Tipo de documento';
$string['form_active'] = '¿Activa?';
$string['form_version'] = 'Versión #';
$string['form_pdffile'] = 'PDF de plantilla';
$string['form_requiredactivities'] = 'Actividades requeridas';
$string['form_requiredactivities_none'] = 'Seleccione las actividades que desbloquean la firma';

$string['template_type_module'] = 'Hito de módulo';
$string['template_type_coursefinal'] = 'Cierre de curso';
$string['versions_createdat'] = 'Creado el';

$string['field_header'] = 'Campo';
$string['field_label'] = 'Etiqueta';
$string['field_source'] = 'Origen de datos';
$string['field_value'] = 'Texto estático (para origen personalizado)';
$string['field_page'] = 'Página';
$string['field_x'] = 'X (px)';
$string['field_y'] = 'Y (px)';
$string['field_fontsize'] = 'Tamaño de fuente / ancho (mm)';
$string['field_width'] = 'Ancho de caja de texto (mm)';
$string['field_size'] = 'Tamaño y ancho';
$string['field_help'] = 'Las coordenadas se miden en milímetros desde la esquina superior izquierda de cada página. Use los orígenes "Firma" o "QR" para colocar imágenes y controle su tamaño con este campo.';
$string['addfield'] = 'Agregar otro campo';

$string['field_source_fullname'] = 'Nombre completo';
$string['field_source_firstname'] = 'Nombre';
$string['field_source_lastname'] = 'Apellido';
$string['field_source_idnumber'] = 'ID / RUT';
$string['field_source_email'] = 'Correo electrónico';
$string['field_source_customtext'] = 'Texto manual (definido en la plantilla)';
$string['field_source_profile'] = 'Campo de perfil: {$a}';
$string['field_source_signature'] = 'Imagen de la firma';
$string['field_source_qr'] = 'Código QR de verificación';
$string['field_source_datesigned'] = 'Fecha de firma';
$string['field_source_coursefullname'] = 'Nombre completo del curso';
$string['fieldeditor'] = 'Editor visual de campos';
$string['fieldeditor_launch'] = 'Abrir editor visual';
$string['fieldeditor_addfield'] = 'Agregar campo';
$string['fieldeditor_save'] = 'Guardar diseño';
$string['fieldeditor_saved'] = 'Diseño guardado correctamente.';
$string['fieldeditor_instructions'] = 'Arrastra cada campo sobre la vista previa del PDF y ajusta el origen, tamaño y etiqueta desde la lista. Los cambios se guardan al confirmar.';
$string['fieldeditor_page'] = 'Página';
$string['fieldeditor_grid'] = 'Mostrar rejilla';
$string['fieldeditor_delete'] = 'Eliminar';
$string['fieldeditor_coordinates'] = 'Coordenadas';
$string['checklist_activity'] = 'Actividad';
$string['checklist_status'] = 'Estado';
$string['checklist_progress'] = 'Progreso';

$string['signing_locked'] = 'Debes completar todas las actividades requeridas antes de firmar.';
$string['signing_canvas_placeholder'] = 'Aquí aparecerá la interfaz de captura de firma (en desarrollo).';
$string['signing_canvas_label'] = 'Dibuja tu firma';
$string['signature_submit'] = 'Enviar firma';
$string['signature_clear'] = 'Limpiar';
$string['signature_required'] = 'Debes ingresar una firma manuscrita antes de enviar.';
$string['signature_success'] = 'Firma registrada correctamente. El PDF estará disponible pronto.';
$string['noversions'] = 'Aún no existen versiones creadas.';
$string['verification_title'] = 'Verificación de documento';
$string['verification_status'] = 'Estado';
$string['verification_signedat'] = 'Fecha de firma';
$string['verification_pdfhash'] = 'Hash del PDF (SHA-256)';
$string['verification_signaturehash'] = 'Hash de la firma (SHA-256)';
$string['verification_download'] = 'Descargar PDF firmado';
$string['verification_notfound'] = 'El token indicado no corresponde a ningún documento firmado.';
$string['preview_pdf'] = 'Vista previa del PDF';
$string['preview_modal_title'] = 'Vista previa del PDF';
$string['preview_notavailable'] = 'No se puede mostrar la vista previa porque falta el PDF de la plantilla.';
$string['error_template_missing'] = 'No se encontró el PDF de la plantilla para esta versión.';
$string['error_signature_decode'] = 'No fue posible decodificar la firma capturada.';
$string['error:templatenotsaved'] = 'Error al guardar la plantilla en la base de datos. Por favor intente nuevamente.';
$string['templatenotfound'] = 'La plantilla solicitada no existe o fue eliminada. Por favor vuelva a la página de gestión de plantillas.';
$string['invalidtemplateid'] = 'ID de plantilla inválido. Por favor seleccione una plantilla válida desde la página de administración de plantillas.';

// Página de inicio.
$string['index_description'] = 'Selecciona un curso a continuación para gestionar plantillas de firma y ver documentos firmados.';
$string['gotocourse'] = 'Ir al curso';
$string['nocourseswithaccess'] = 'No tienes permisos para gestionar plantillas de firma en ningún curso.';

$string['task_send_reminders'] = 'Enviar recordatorios de firmas pendientes';
$string['reminder_subject'] = 'Documento pendiente de firma';
$string['reminder_body'] = 'Aún tienes un documento en espera de firma. Por favor vuelve al curso y completa el proceso.';
