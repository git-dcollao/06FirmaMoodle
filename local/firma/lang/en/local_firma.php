<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

$string['pluginname'] = 'Digital signatures';
$string['privacy:metadata'] = 'The Local Firma plugin stores template metadata, signatures, and reminder logs to comply with auditing requirements.';
$string['privacy:metadata:templates'] = 'Template metadata stored per course.';
$string['privacy:metadata:templates:courseid'] = 'Course identifier that owns the template.';
$string['privacy:metadata:templates:name'] = 'Template name defined by teachers.';
$string['privacy:metadata:signatures'] = 'Signature evidence per user.';
$string['privacy:metadata:signatures:userid'] = 'The user who signed the document.';
$string['privacy:metadata:signatures:signedpdfid'] = 'Reference to the stored signed PDF.';
$string['privacy:metadata:signatures:ip'] = 'IP address captured at signing time.';
$string['privacy:metadata:signatures:useragent'] = 'User agent string captured at signing time.';
$string['privacy:metadata:signatures:signaturehash'] = 'Hash of the drawn signature strokes.';
$string['privacy:metadata:signatures:pdfhash'] = 'Hash of the finalized PDF.';
$string['privacy:metadata:signatures:token'] = 'Verification token embedded in the QR code.';
$string['privacy:metadata:reminders'] = 'Reminder notifications sent to users.';
$string['privacy:metadata:reminders:signatureid'] = 'Signature record that triggered the reminder.';
$string['privacy:metadata:reminders:sentat'] = 'Timestamp when the reminder was sent.';

$string['firma:managetemplates'] = 'Manage signature templates';
$string['firma:viewreports'] = 'View signature reports';
$string['firma:sign'] = 'Sign assigned documents';
$string['firma:downloadall'] = 'Download all signed documents';

$string['settings_maxpdfpages'] = 'Maximum PDF pages';
$string['settings_maxpdfpages_desc'] = 'Safety limit for teacher-uploaded templates (number of pages per PDF).';
$string['settings_reminderinterval'] = 'Reminder interval';
$string['settings_reminderinterval_desc'] = 'Period between reminder notifications for pending signatures.';
$string['settings_enableqr'] = 'Enable QR verification';
$string['settings_enableqr_desc'] = 'When enabled, a QR code is embedded in each signed PDF linking to the verification endpoint.';

// UI strings.
$string['manage_templates'] = 'Manage templates';
$string['addtemplate'] = 'Add template';
$string['manageversions'] = 'Manage versions';
$string['signatures'] = 'Signatures';
$string['templates'] = 'Templates';
$string['versions'] = 'Versions';
$string['actions'] = 'Actions';
$string['status_active'] = 'Active';
$string['status_inactive'] = 'Inactive';
$string['status_signed'] = 'Signed';
$string['status_pending'] = 'Pending';
$string['status_revoked'] = 'Revoked';
$string['status_draft'] = 'Draft';
$string['event_template_updated'] = 'Template updated';
$string['event_signature_completed'] = 'Document signed';

$string['form_name'] = 'Display name';
$string['form_description'] = 'Description';
$string['form_section'] = 'Section binding';
$string['form_section_none'] = 'Entire course';
$string['form_type'] = 'Document type';
$string['form_active'] = 'Active?';
$string['form_version'] = 'Version #';
$string['form_pdffile'] = 'Template PDF';
$string['form_requiredactivities'] = 'Required activities';
$string['form_requiredactivities_none'] = 'Select activities to unlock signature';

$string['template_type_module'] = 'Module checkpoint';
$string['template_type_coursefinal'] = 'Course completion';
$string['versions_createdat'] = 'Created at';

$string['field_header'] = 'Field';
$string['field_label'] = 'Label';
$string['field_source'] = 'Data source';
$string['field_value'] = 'Static text (for custom text source)';
$string['field_page'] = 'Page';
$string['field_x'] = 'X (px)';
$string['field_y'] = 'Y (px)';
$string['field_fontsize'] = 'Font size / width (mm)';
$string['field_width'] = 'Text box width (mm)';
$string['field_size'] = 'Size and width';
$string['field_help'] = 'Coordinates are measured in millimeters from the top-left corner of each page. Use "Signature" or "QR" sources to place images, and set the size field to control their width.';
$string['addfield'] = 'Add another field';

$string['field_source_fullname'] = 'Full name';
$string['field_source_firstname'] = 'First name';
$string['field_source_lastname'] = 'Last name';
$string['field_source_idnumber'] = 'ID / RUT';
$string['field_source_email'] = 'Email';
$string['field_source_customtext'] = 'Manual text (enter when generating PDF)';
$string['field_source_profile'] = 'Profile field: {$a}';
$string['field_source_signature'] = 'Signature image';
$string['field_source_qr'] = 'Verification QR code';
$string['field_source_datesigned'] = 'Signature date';
$string['field_source_coursefullname'] = 'Course full name';
$string['fieldeditor'] = 'Visual layout editor';
$string['fieldeditor_launch'] = 'Open visual editor';
$string['fieldeditor_addfield'] = 'Add field';
$string['fieldeditor_save'] = 'Save layout';
$string['fieldeditor_saved'] = 'Layout saved successfully.';
$string['fieldeditor_instructions'] = 'Drag each field over the PDF preview and tweak its source, size, and label from the list. Changes are stored when you save.';
$string['fieldeditor_page'] = 'Page';
$string['fieldeditor_grid'] = 'Show grid';
$string['fieldeditor_delete'] = 'Remove';
$string['fieldeditor_coordinates'] = 'Coordinates';
$string['checklist_activity'] = 'Activity';
$string['checklist_status'] = 'Status';
$string['checklist_progress'] = 'Progress';

$string['signing_locked'] = 'You must complete all required activities before signing.';
$string['signing_canvas_placeholder'] = 'Signature capture UI will appear here (in development).';
$string['signing_canvas_label'] = 'Draw your signature';
$string['signature_submit'] = 'Submit signature';
$string['signature_clear'] = 'Clear';
$string['signature_required'] = 'Please provide a handwritten signature before submitting.';
$string['signature_success'] = 'Signature stored successfully. The PDF will be available shortly.';
$string['noversions'] = 'No versions created yet.';
$string['verification_title'] = 'Document verification';
$string['verification_status'] = 'Status';
$string['verification_signedat'] = 'Signed at';
$string['verification_pdfhash'] = 'PDF hash (SHA-256)';
$string['verification_signaturehash'] = 'Signature hash (SHA-256)';
$string['verification_download'] = 'Download signed PDF';
$string['verification_notfound'] = 'The provided token does not match any signed document.';
$string['preview_pdf'] = 'Preview PDF';
$string['preview_modal_title'] = 'PDF preview';
$string['preview_notavailable'] = 'Preview unavailable because the template PDF was not found.';
$string['error_template_missing'] = 'Template PDF not found for this version.';
$string['error_signature_decode'] = 'The captured signature could not be decoded.';
$string['error:templatenotsaved'] = 'Error saving the template to the database. Please try again.';
$string['templatenotfound'] = 'The requested template does not exist or has been deleted. Please return to the manage templates page.';
$string['invalidtemplateid'] = 'Invalid template ID. Please select a valid template from the manage templates page.';

// Index page.
$string['index_description'] = 'Select a course below to manage signature templates and view signed documents.';
$string['gotocourse'] = 'Go to course';
$string['nocourseswithaccess'] = 'You do not have permission to manage signature templates in any course.';

// Tasks.
$string['task_send_reminders'] = 'Send pending signature reminders';
$string['reminder_subject'] = 'Pending document signature';
$string['reminder_body'] = 'You still have a document awaiting signature. Please return to the course and complete the process.';
