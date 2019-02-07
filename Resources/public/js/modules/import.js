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
                let duplicateAssignations = document.querySelectorAll(`select[name^="assignation["]:not([name="${e.target.getAttribute('name')}"]) option[value="${e.target.value}"]:checked`);
                for (let option of duplicateAssignations) {
                    option.closest('select').value = '';
                }

                for (let option of document.querySelectorAll('option[selected]:not(:checked)')) {
                    option.removeAttribute('selected');
                }

                importFlow.updateAssignations();
            }
        });

        importFlow.previewWrapper.addEventListener('submit', function(e){
            BundleUI.clearFlashMessages();

            // Check that all required properties have been assigned to a column
            let properties = typeof importFlow.settings.properties != 'undefined' ? JSON.parse(JSON.stringify(importFlow.settings.properties)) : {};

            // Remove optional properties from the list
            for (let property of Object.keys(properties)) {
                if (typeof properties[property].required == 'undefined' || !properties[property].required) {
                    delete properties[property];
                }
            }

            // Check that all remaining properties have been assigned
            for (let select of document.querySelectorAll('select[name^="assignation["]')) {
                if (select.value && typeof properties[select.value] != 'undefined') {
                    delete properties[select.value];
                }
            }

            // Remaining properties are required and unassigned.
            if (Object.keys(properties).length) {
                e.preventDefault();

                let propertyLabels = [];
                for (let property of Object.keys(properties)) {
                    propertyLabels.push(document.querySelector('option[value="' + property + '"]').innerHTML.trim());
                }

                BundleUI.showFlashMessage('error', trans('import.errors.assignations.requiredProperties', { '%properties%': propertyLabels.join(', ') }, 'application'));

                return false;
            }

            importFlow.previewWrapper.querySelector('input[name="data"]').value = JSON.stringify(importFlow.computeData());
        });

        // Columns highlighting when hovering the assignations
        document.querySelector("body").addEventListener("mouseenter", function(e){
        	if (!e.target.matches(".assignation-row, .assignation-row *")) {
        		return;
            }

        	let highlightColor = '#f8f8f8';
        	let indicator = e.target.matches(".assignation-row") ? e.target : e.target.closest(".assignation-row");
            let columnKey = indicator.querySelector(".column").innerHTML.trim();

        	// Highlight the indicator
            indicator.style.backgroundColor = highlightColor;

        	// Higlight column inside the table
        	let columnCells = document.querySelectorAll(".excel-preview [data-number='" + columnKey + "']");
        	for (let cell of columnCells) {
        		cell.style.backgroundColor = highlightColor;
            }
        }, true);
        // Undo column highlighting on mouseleave
        document.querySelector("body").addEventListener("mouseleave", function(e){
        	if (!e.target.matches(".assignation-row, .assignation-row *")) {
        		return;
            }

        	let indicator = e.target.matches(".assignation-row") ? e.target : e.target.closest(".assignation-row");
            let columnKey = indicator.querySelector(".column").innerHTML.trim();

        	// Highlight the indicator
            indicator.style.backgroundColor = null;

        	// Higlight column inside the table
        	let columnCells = document.querySelectorAll(".excel-preview [data-number='" + columnKey + "']");
        	for (let cell of columnCells) {
        		cell.style.backgroundColor = null;
            }
        }, true);
    }

    initLoadedFile() {
        this.fileSelector.classList.add('hide');
        this.form.querySelector('.change-file').classList.remove('hide');
        this.previewWrapper.classList.remove('hide');
        this.processingWrapper.classList.remove('hide');

        if (this.rawData.length > 1) {
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

        this.selectDefaultWorksheet();

        // Trigger the change event to initialize settings and display the preview
        this.sheetInput.dispatchEvent(new Event('change'));
    }

    selectDefaultWorksheet() {
        if (typeof this.settings.defaults.worksheet == 'undefined') {
            return;
        }

        let bestMatch = null;
        let bestMatchSimilarity = 0;

        for (let option of this.sheetInput.querySelectorAll('option')) {
            let label = option.innerHTML.trim();

            for (let title of this.settings.defaults.worksheet) {
                let similarity = stringSimilarity(label, title.trim());
                if (similarity > bestMatchSimilarity) {
                    bestMatch = option;
                    bestMatchSimilarity = similarity;
                }
            }
        }

        if (bestMatch) {
            bestMatch.selected = true;
        }
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

        let defaultOption = select.querySelector(`option[data-key="${defaultValue}"]`);
        if (defaultOption) {
            defaultOption.setAttribute('selected', 'selected');
        }

        return select;
    }

    generatePreview() {
        // Before we get started, get all existing assignations selects
        // We'll be reusing them to avoid losing assignations that are already set-up
        let assignationSelects = document.querySelectorAll('select[name^="assignation["]');
        let existingSelects = [];
        for (let select of assignationSelects) {
            let newSelect = select.cloneNode(true);
            newSelect.querySelector('option[value="' + select.value + '"]').setAttribute('selected', 'selected');
            existingSelects.push(newSelect);
        }

        let data = this.computeData();
        let html = `<thead>
                        <th class="row-number"></th>`;

        if (data.length) {
            for (let i = 0; i < data[0].length; i++) {


                html += `<th class="column-number" data-number="${ImportFlow.getColumnNameFromNumber(i)}">
                            <span class='number'>${ImportFlow.getColumnNameFromNumber(i)}</span>
                            ${(typeof existingSelects[i] != 'undefined' ? existingSelects[i] : this.createAssignationSelect(i)).outerHTML}
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
                rowHtml += `<td class="cell" data-number="${ImportFlow.getColumnNameFromNumber(colIndex)}">${cell ? '<div class="cell-value">' + cell + '</div>' : ''}</td>`;
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
        this.updateAssignations();
    }

    updateRepeatableRelationOptgroups() {
        let assignationSelects = document.querySelectorAll('select[name^="assignation["]');

        // Handle repeatable relations
        if (typeof this.settings.properties == 'undefined' || !assignationSelects.length) {
            return;
        }

        let newRelationOptgroups = {};
        for (let propertyKey in this.settings.properties) {
            let property = this.settings.properties[propertyKey];

            if (typeof property.relation == 'undefined' || typeof property.repeatable == 'undefined' || !property.repeatable) {
                continue;
            }

            // First, make sure there's no unused relation optgroups with used optgroups for the same relation at a higher index
            let usedOptgroups = [];
            let selectedOptions = document.querySelectorAll('select[name^="assignation["] optgroup[property="' + propertyKey + '"] option:checked');
            for (let option of selectedOptions) {
                let optgroupIndex = option.closest('optgroup').getAttribute('data-index');
                if (usedOptgroups.indexOf(optgroupIndex) == -1) {
                    usedOptgroups.push(parseInt(optgroupIndex));
                }
            }

            let unusedIndex = null;
            let sampleSelectOptgroups = document.querySelector('select[name^="assignation["]').querySelectorAll('optgroup[property="' + propertyKey + '"]');
            if (usedOptgroups.length < sampleSelectOptgroups.length - 1) {
                for (let i = 0; i < sampleSelectOptgroups.length; i++) {
                    if (usedOptgroups.indexOf(i) == -1) {
                        unusedIndex = i;
                        break;
                    }
                }
            }

            if (unusedIndex !== null) {
                for (let select of assignationSelects) {
                    for (let optgroup of select.querySelectorAll('optgroup[property="' + propertyKey + '"]')) {
                        if (parseInt(optgroup.getAttribute('data-index')) == unusedIndex) {
                            optgroup.parentNode.removeChild(optgroup);
                        } else if (parseInt(optgroup.getAttribute('data-index')) > unusedIndex) {
                            optgroup.setAttribute('label', optgroup.getAttribute('original-label') + ' (' + optgroup.getAttribute('data-index') + ')');
                            optgroup.setAttribute('data-index', parseInt(optgroup.getAttribute('data-index')) - 1);
                            for (let option of optgroup.querySelectorAll('option')) {
                                option.setAttribute('value', option.getAttribute('value').replace(/\.[0-9]+\./, '.' + parseInt(optgroup.getAttribute('data-index')) + '.'));
                            }
                        }
                    }
                }
            }

            // If need be, create new option group in each select for the next instance of the relation
            for (let select of assignationSelects) {
                let optgroups = select.querySelectorAll('optgroup[property="' + propertyKey + '"]');

                if (optgroups.length) {
                    let lastOptgroup = optgroups[optgroups.length - 1];
                    if (lastOptgroup.querySelector('option:checked')) {
                        let newOptgroup = lastOptgroup.cloneNode(true);
                        newOptgroup.setAttribute('label', lastOptgroup.getAttribute('original-label') + ' (' + (optgroups.length + 1) + ')');
                        newOptgroup.setAttribute('data-index', optgroups.length);

                        if (newOptgroup.querySelector('option:checked')) {
                            newOptgroup.querySelector('option:checked').checked = false;
                        }

                        for (let option of newOptgroup.querySelectorAll('option')) {
                            option.setAttribute('value', option.getAttribute('value').replace(/\.[0-9]+\./, '.' + optgroups.length + '.'));
                        }

                        newRelationOptgroups[propertyKey] = newOptgroup;
                    }
                }
            }
        }

        // Insert the new relation optgroups, if any
        if (Object.keys(newRelationOptgroups).length) {
            for (let select of assignationSelects) {
                for (let propertyKey in newRelationOptgroups) {
                    let optgroups = select.querySelectorAll('optgroup[property="' + propertyKey + '"]');
                    BundleUI.insertAfter(newRelationOptgroups[propertyKey].cloneNode(true), optgroups[optgroups.length - 1]);
                }
            }
        }
    }

    updateAssignations() {
        this.updateRepeatableRelationOptgroups();

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
