define([], function() {
    const MM_PER_POINT = 25.4 / 72;
    const SCALE = 1.2;

    const defaults = {
        label: '',
        source: 'customtext',
        page: 1,
        x: 0,
        y: 0,
        fontsize: 12,
        width: 0,
        value: ''
    };

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => {
        switch (char) {
            case '&':
                return '&amp;';
            case '<':
                return '&lt;';
            case '>':
                return '&gt;';
            case '"':
                return '&quot;';
            case '\'':
                return '&#39;';
            default:
                return char;
        }
    });

    const normaliseField = (field, id) => ({
        ...defaults,
        ...field,
        id,
        page: Math.max(1, parseInt(field.page ?? defaults.page, 10) || 1),
        x: parseFloat(field.x ?? defaults.x) || 0,
        y: parseFloat(field.y ?? defaults.y) || 0,
        fontsize: parseFloat(field.fontsize ?? defaults.fontsize) || defaults.fontsize,
        width: parseFloat(field.width ?? defaults.width) || 0
    });

    const init = () => {
        const root = document.querySelector('[data-region="local-firma-fieldeditor"]');
        if (!root) {
            return;
        }

        // Leer configuración desde atributos data-*
        const configData = root.getAttribute('data-editor-config');
        const stringsData = root.getAttribute('data-strings');
        
        if (!configData) {
            console.error('No editor configuration found');
            return;
        }

        let config;
        let strings;
        try {
            config = JSON.parse(configData);
            strings = stringsData ? JSON.parse(stringsData) : {};
        } catch (e) {
            console.error('Failed to parse editor configuration', e);
            return;
        }

        // Verificar si PDF.js está disponible
        if (typeof window.pdfjsLib === 'undefined') {
            console.warn('PDF.js not loaded, editor may not work properly');
            // Continuar de todas formas para que el formulario funcione
        }

        window.pdfjsLib.GlobalWorkerOptions.workerSrc = M.cfg.wwwroot + '/lib/pdfjs/pdf.worker.js';

        const canvas = document.getElementById('local-firma-editor-canvas');
        const overlay = root.querySelector('[data-region="overlay"]');
        const pageSelect = document.getElementById('local-firma-editor-page');
        const gridToggle = document.getElementById('local-firma-editor-grid');
        const fieldList = document.getElementById('local-firma-fieldlist');
        const addButton = document.getElementById('local-firma-add-field');
        const jsonInput = document.getElementById('local-firma-fieldsjson');
        const form = document.getElementById('local-firma-fieldeditor-form');

        let pdfDoc = null;
        let currentPage = 1;
        let activeFieldId = null;
        let dragState = null;
        const mmPerPx = MM_PER_POINT / SCALE;

        let fields = (config.fields || []).map((field, index) => normaliseField(field, index));
        let uid = fields.length;

        const getFieldById = (id) => fields.find((field) => field.id === id);

        const setActive = (id) => {
            activeFieldId = id;
            highlightActive();
        };

        const highlightActive = () => {
            root.querySelectorAll('.local-firma-field-row').forEach((row) => {
                row.classList.toggle('is-active', parseInt(row.dataset.fieldId, 10) === activeFieldId);
            });
            root.querySelectorAll('.local-firma-field-box').forEach((box) => {
                box.classList.toggle('is-active', parseInt(box.dataset.fieldId, 10) === activeFieldId);
            });
        };

        const pxToMm = (px) => parseFloat((px * mmPerPx).toFixed(2));
        const mmToPx = (mm) => mm / mmPerPx;

        const getLabel = (key) => strings[key] || '';

        const renderFieldList = () => {
            fieldList.innerHTML = '';
            fields.forEach((field) => {
                const row = document.createElement('div');
                row.className = 'local-firma-field-row';
                row.dataset.fieldId = field.id;
                row.innerHTML = `
                    <div class="local-firma-field-row__header">
                        <input type="text" class="form-control field-label" value="${escapeHtml(field.label)}" placeholder="${escapeHtml(getLabel('fieldLabel'))}">
                        <button type="button" class="btn btn-icon btn-link field-delete" aria-label="${strings.deleteField}">×</button>
                    </div>
                    <div class="local-firma-field-row__body">
                        <label>
                            <span>${escapeHtml(getLabel('fieldSource'))}</span>
                            <select class="custom-select field-source">
                                ${getSourceOptions(field.source)}
                            </select>
                        </label>
                        <label>
                            <span>${escapeHtml(getLabel('fieldPage'))}</span>
                            <input type="number" class="form-control field-page" value="${field.page}" min="1">
                        </label>
                        <label>
                            <span>${escapeHtml(getLabel('fieldX'))}</span>
                            <input type="number" class="form-control field-x" step="0.5" value="${field.x}">
                        </label>
                        <label>
                            <span>${escapeHtml(getLabel('fieldY'))}</span>
                            <input type="number" class="form-control field-y" step="0.5" value="${field.y}">
                        </label>
                        <label>
                            <span>${escapeHtml(getLabel('fieldFont'))}</span>
                            <input type="number" class="form-control field-fontsize" step="0.5" value="${field.fontsize}">
                        </label>
                        <label>
                            <span>${escapeHtml(getLabel('fieldWidth'))}</span>
                            <input type="number" class="form-control field-width" step="0.5" value="${field.width}">
                        </label>
                        <label>
                            <span>${escapeHtml(getLabel('fieldValue'))}</span>
                            <input type="text" class="form-control field-value" value="${escapeHtml(field.value)}">
                        </label>
                    </div>
                `;
                fieldList.appendChild(row);
            });
            highlightActive();
        };

        const getSourceOptions = (selected) => {
            return Object.entries(config.fieldOptions || {}).map(([value, label]) => {
                const isSelected = value === selected ? 'selected' : '';
                return `<option value="${escapeHtml(value)}" ${isSelected}>${escapeHtml(label)}</option>`;
            }).join('');
        };

        const renderBoxes = () => {
            overlay.innerHTML = '';
            const filtered = fields.filter((field) => field.page === currentPage);
            filtered.forEach((field) => {
                overlay.appendChild(createBox(field));
            });
            highlightActive();
        };

        const createBox = (field) => {
            const box = document.createElement('div');
            box.className = 'local-firma-field-box';
            box.dataset.fieldId = field.id;
            box.textContent = field.label || config.fieldOptions[field.source] || field.source;
            box.style.left = `${mmToPx(field.x)}px`;
            box.style.top = `${mmToPx(field.y)}px`;
            const widthPx = field.width > 0 ? mmToPx(field.width) : 80;
            box.style.width = `${widthPx}px`;
            box.style.fontSize = `${Math.max(8, field.fontsize)}px`;
            box.addEventListener('pointerdown', (event) => startDrag(event, field, box));
            box.addEventListener('click', () => setActive(field.id));
            return box;
        };

        const startDrag = (event, field, node) => {
            event.preventDefault();
            setActive(field.id);
            dragState = {
                id: field.id,
                startX: event.clientX,
                startY: event.clientY,
                originLeft: mmToPx(field.x),
                originTop: mmToPx(field.y)
            };
            document.addEventListener('pointermove', handleDragMove);
            document.addEventListener('pointerup', stopDrag);
        };

        const handleDragMove = (event) => {
            if (!dragState) {
                return;
            }
            const field = getFieldById(dragState.id);
            if (!field) {
                return;
            }
            const deltaX = event.clientX - dragState.startX;
            const deltaY = event.clientY - dragState.startY;
            const maxX = overlay.clientWidth - 10;
            const maxY = overlay.clientHeight - 10;
            const newLeft = clamp(dragState.originLeft + deltaX, 0, maxX);
            const newTop = clamp(dragState.originTop + deltaY, 0, maxY);
            field.x = pxToMm(newLeft);
            field.y = pxToMm(newTop);
            renderBoxes();
            updateFieldRow(field);
        };

        const stopDrag = () => {
            document.removeEventListener('pointermove', handleDragMove);
            document.removeEventListener('pointerup', stopDrag);
            dragState = null;
        };

        const updateFieldRow = (field) => {
            const row = fieldList.querySelector(`.local-firma-field-row[data-field-id="${field.id}"]`);
            if (!row) {
                return;
            }
            row.querySelector('.field-x').value = field.x;
            row.querySelector('.field-y').value = field.y;
        };

        const deleteField = (id) => {
            fields = fields.filter((field) => field.id !== id);
            if (activeFieldId === id) {
                activeFieldId = null;
            }
            renderFieldList();
            renderBoxes();
        };

        const addField = () => {
            const newField = normaliseField({
                label: getLabel('fieldLabel') || strings.coordinates || 'Field',
                page: currentPage,
                x: 10,
                y: 10
            }, uid++);
            fields.push(newField);
            renderFieldList();
            renderBoxes();
            setActive(newField.id);
        };

        const handleFieldInput = (event) => {
            const row = event.target.closest('.local-firma-field-row');
            if (!row) {
                return;
            }
            const id = parseInt(row.dataset.fieldId, 10);
            const field = getFieldById(id);
            if (!field) {
                return;
            }

            if (event.target.classList.contains('field-label')) {
                field.label = event.target.value;
            } else if (event.target.classList.contains('field-source')) {
                field.source = event.target.value;
            } else if (event.target.classList.contains('field-page')) {
                field.page = Math.max(1, parseInt(event.target.value, 10) || 1);
            } else if (event.target.classList.contains('field-x')) {
                field.x = parseFloat(event.target.value) || 0;
            } else if (event.target.classList.contains('field-y')) {
                field.y = parseFloat(event.target.value) || 0;
            } else if (event.target.classList.contains('field-fontsize')) {
                field.fontsize = Math.max(6, parseFloat(event.target.value) || 12);
            } else if (event.target.classList.contains('field-width')) {
                field.width = Math.max(0, parseFloat(event.target.value) || 0);
            } else if (event.target.classList.contains('field-value')) {
                field.value = event.target.value;
            }
            renderBoxes();
        };

        fieldList.addEventListener('click', (event) => {
            const row = event.target.closest('.local-firma-field-row');
            if (!row) {
                return;
            }
            const id = parseInt(row.dataset.fieldId, 10);
            if (event.target.classList.contains('field-delete')) {
                deleteField(id);
                event.preventDefault();
                return;
            }
            setActive(id);
        });

        fieldList.addEventListener('input', handleFieldInput);
        addButton.addEventListener('click', addField);

        pageSelect.addEventListener('change', () => {
            currentPage = parseInt(pageSelect.value, 10) || 1;
            renderBoxes();
        });

        gridToggle.addEventListener('change', () => {
            overlay.classList.toggle('show-grid', gridToggle.checked);
        });

        form.addEventListener('submit', () => {
            const serialised = fields.map((field) => ({
                label: field.label,
                source: field.source,
                page: field.page,
                x: field.x,
                y: field.y,
                fontsize: field.fontsize,
                width: field.width,
                value: field.value
            }));
            jsonInput.value = JSON.stringify(serialised);
        });

        const prepareDocument = () => {
            window.pdfjsLib.getDocument(config.pdfUrl).promise.then((doc) => {
                pdfDoc = doc;
                populatePageOptions(doc.numPages);
                renderPage();
            });
        };

        const populatePageOptions = (pages) => {
            pageSelect.innerHTML = '';
            for (let page = 1; page <= pages; page++) {
                const option = document.createElement('option');
                option.value = page;
                option.textContent = `${page}`;
                pageSelect.appendChild(option);
            }
            currentPage = 1;
            pageSelect.value = '1';
        };

        const renderPage = () => {
            if (!pdfDoc) {
                return;
            }
            pdfDoc.getPage(currentPage).then((page) => {
                const viewport = page.getViewport({ scale: SCALE });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                canvas.style.width = viewport.width + 'px';
                canvas.style.height = viewport.height + 'px';
                overlay.style.width = viewport.width + 'px';
                overlay.style.height = viewport.height + 'px';
                const context = canvas.getContext('2d');
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                page.render(renderContext).promise.then(() => {
                    renderBoxes();
                });
            });
        };

        renderFieldList();
        if (fields.length) {
            setActive(fields[0].id);
        }
        prepareDocument();
    };

    return { init };
});
