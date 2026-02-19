# Local Firma Plugin Blueprint

## Overview
Local plugin for Moodle 5.1 that manages document signing workflows with checklists, template versioning, embedded handwritten signatures, QR verification, and legal auditing (Chile Ley 19.799 - FES).

## Key Concepts
- **Templates**: Course-scoped document definitions with optional section (module) binding and type (`module`, `coursefinal`).
- **Template Versions**: Each version stores PDF file, field layout metadata, checklist configuration, required activities, and completion rules.
- **Signatures**: Per-user evidence containing signed PDF file reference, hashes, IP, user agent, and completion evidence snapshot.
- **Reminders**: Cron-driven notifications for pending signatures.
- **Progress Tracking**: Optional per-activity progress (e.g., video completion) recorded via AJAX endpoint.

## Tables
1. `local_firma_templates` – template metadata, course linkage, type, active flag.
2. `local_firma_template_versions` – version records referencing stored PDF, JSON field layout, required activities, completion rule.
3. `local_firma_signatures` – signature history, references version, user, signed PDF, hashes, token for QR verification, status, completion snapshot.
4. `local_firma_reminders` – reminder logs tied to signatures.
5. `local_firma_progress` – optional per cmid progress for custom tracking (0-100%).

## Services
- `template_manager` – CRUD for templates/versions, cloning, activation.
- `checklist_service` – resolves completion status using Moodle completion API + custom progress.
- `signature_service` – merges data into PDF (FPDI/TCPDF), embeds signature PNG and QR, stores files/hashes/logs.
- `reminder_service` – schedules and sends notifications for pending signatures.

## Frontend Modules
- Template field editor: PDF.js rendering with draggable markers.
- Checklist display: shows required activities and progress bars.
- Signature capture: Canvas (Signature Pad) overlay with validation.

## Compliance & Auditing
- Event logging for template updates and signature actions.
- Stored hashes (SHA-256) for signed PDFs and raw signature strokes.
- QR verification endpoint exposes metadata and download link to confirm authenticity or revocation state.

## External Libraries (Composer)
- `tecnickcom/tcpdf`
- `setasign/fpdi`
- `endroid/qr-code`

## Next Steps
1. Scaffold plugin directories/files.
2. Implement database schema and basic admin settings.
3. Stub core services, tasks, and privacy provider.
4. Add composer autoload and vendor requirements.
