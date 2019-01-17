class ImportFlow {
    constructor(settings) {
        this.settings = settings;
        this.form = document.querySelector('form[name="import"]');
        this.fileSelector = this.form.querySelector('.file-selector');
        this.processingWrapper = document.querySelector('.import-processing');
        this.sheetSelector = document.querySelector('.field-wrapper.select-sheet');
        this.sheetInput = document.querySelector('select.worksheet');
        this.startingLineInput = document.querySelector('input.starting-line');
        this.previewWrapper = document.querySelector('.import-preview');
        this.preview = this.previewWrapper.querySelector('.excel-preview');
        this.rawData = null;

        this.initFileUpload();
        this.initProcessing();
    }

    static init(settings) {
        return new ImportFlow(settings);
    }

    initFileUpload() {
        let importFlow = this;

        // File upload: error handling
        importFlow.form.addEventListener('ajax-error', function(e) {
            // @TODO
        });

        // File upload: process successful request
        importFlow.form.addEventListener('ajax-response', function(e) {
            importFlow.rawData = e.detail;
            importFlow.initLoadedFile();
        });

        // Reset button
        importFlow.form.querySelector('button.import-reset').addEventListener('click', function(e){
            e.preventDefault();
            importFlow.reset();
        });
    }

    initProcessing() {
        let importFlow = this;

        // Worksheet selection
        importFlow.sheetInput.addEventListener('change', function(e) {
            importFlow.updateSettingsInputs();
            importFlow.generatePreview();
            importFlow.updateAssignations();
        });

        // Starting line handling
        importFlow.startingLineInput.addEventListener('change', function(e) {
            importFlow.generatePreview();
        });

        // Starting line handling
        document.addEventListener('change', function(e) {
            if (e.target.matches('select[name^="assignation["]')) {
                importFlow.updateAssignations();
            }
        });

        importFlow.previewWrapper.addEventListener('submit', function(e){
            e.preventDefault();
        });
    }

    initLoadedFile() {
        this.fileSelector.classList.add('hide');
        this.form.querySelector('.change-file').classList.remove('hide');
        this.previewWrapper.classList.remove('hide');
        this.processingWrapper.classList.remove('hide');

        if (this.rawData.length) {
            this.sheetSelector.classList.remove('hide');

            let optionsHtml = '';
            for (let sheetIndex = 0; sheetIndex < this.rawData.length; sheetIndex++) {
                optionsHtml += `<option value="${sheetIndex}">${this.rawData[sheetIndex].title ? this.rawData[sheetIndex].title : trans('import.fields.unnamedSheet', {}, 'application')}</option>`;
            }
            this.sheetInput.innerHTML = optionsHtml;
            this.sheetInput.value = 0;
        } else {
            this.sheetSelector.classList.add('hide');
        }

        // Trigger the change event to initialize settings and display the preview
        this.sheetInput.dispatchEvent(new Event('change'));
    }

    updateSettingsInputs() {
        let data = this.computeData();
        this.startingLineInput.value = 1;
        this.startingLineInput.setAttribute('max', data.length);
    }

    reset() {
        this.fileSelector.classList.remove('hide')
        this.processingWrapper.classList.add('hide');
        this.form.querySelector('input[type="file"]').value = '';
        this.form.querySelector('.change-file').classList.add('hide');
        this.sheetSelector.classList.add('hide');
        this.sheetInput.innerHTML = '';
        this.sheetInput.value = '';
    }

    computeData() {
        // @TODO
        return this.rawData ? this.rawData[this.sheetInput.value]['rows'] : [];
    }

    createAssignationSelect(columnIndex) {
        let assignationSelectTemplate = document.querySelector('.fields.assignations select');
        let columnName = ImportFlow.getColumnNameFromNumber(columnIndex);
        let select = assignationSelectTemplate.cloneNode(true);
        let defaultValue;

        try {
            defaultValue = this.settings.defaults.columns[columnName];
        } catch (e) {
            defaultValue = null;
        }

        select.classList.remove('hide');
        select.setAttribute('name', 'assignation[' + columnIndex + ']');

        let defaultOption = select.querySelector(`option[value="${defaultValue}"]`);
        if (defaultOption) {
            defaultOption.setAttribute('selected', 'selected');
        }

        return select;
    }

    generatePreview() {
        let data = this.computeData();
        let html = `<thead>
                        <th class="row-number"></th>`;

        if (data.length) {
            for (let i = 0; i < data[0].length; i++) {


                html += `<th class="column-number">
                            <span class='number'>${ImportFlow.getColumnNameFromNumber(i)}</span>
                            ${this.createAssignationSelect(i).outerHTML}
                        </th>`;
            }
        }

        html += `</thead>
                <tbody>`;

        let startingLine = this.startingLineInput.value - 1;

        for (let rowIndex = 0; rowIndex < data.length; rowIndex++) {
            if (rowIndex < startingLine) {
                continue;
            }

            let row = data[rowIndex];
            let rowHtml = `<th class="row-number">${rowIndex + 1}</th>`;

            for (let colIndex = 0; colIndex < row.length; colIndex++) {
                let cell = row[colIndex];
                rowHtml += `<td class="cell">${cell ? '<div class="cell-value">' + cell + '</div>' : ''}</td>`;
            }

            html += `<tr class="row">${rowHtml}</tr>`;
        }

        html += `</tbody>`;

        if (data.length - startingLine > 20) {
            html += `<tfoot>
                        <th colspan="${data[0].length + 1}" class="hidden-rows-label">
                            ${trans('import.excel.partialMissingRows', { '%rowCount%': data.length - startingLine - 20 }, 'application')}
                        </th>
                    </tfoot>`
        }

        this.preview.innerHTML = html;
    }

    updateAssignations() {
        let data = this.computeData();
        let wrapper = document.querySelector('.fields.assignations .columns');
        let assignationSelects = document.querySelectorAll('select[name^="assignation["]');

        BundleUI.empty(wrapper);

        for (let select of assignationSelects) {
            let selectedOption = select.querySelector('option:checked');
            if (selectedOption && selectedOption.value) {
                let columnName = select.previousElementSibling.innerHTML.trim();
                let valueLabel = selectedOption.innerHTML.trim();
                let groupLabel = selectedOption.parentNode.getAttribute('label').trim();

                BundleUI.appendTo(wrapper, `<div class="assignation-row">
                                                <span class="column">${columnName}</span>
                                                <span class="field">${groupLabel}: ${valueLabel}</span>
                                            </div>`);
            }
        }
    }

    static getColumnNameFromNumber(number) {
        let alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let numeric = number % 26;
        let letter = alphabet[numeric];
        let num2 = parseInt(number / 26);

        if (num2 > 0) {
            return ImportFlow.getColumnNameFromNumber(num2 - 1) + letter;
        }

        return letter;
    }
}
