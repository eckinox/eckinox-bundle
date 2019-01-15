let content = document.querySelector('.content.application'),
    template = document.querySelector('.json-row.hide');

content.addEventListener('click', function(e) {
    let addButton = e.target;

    if (e.target.matches('.json-add') || (addButton = e.target.closest('.json-add') && addButton)) {
        let row = addButton.closest('.json-row'),
            new_row = template.cloneNode(true),
            original_name = row.querySelector(':scope > .json-key input').name,
            // The "+" means we've got a new field to add
            new_name = original_name.substring(0, original_name.lastIndexOf("[") + 1) + "+" + guid("") + "]";

        new_row.classList.remove('hide');
        for (let addElement of new_row.querySelectorAll('.json-add')) {
            addElement.parentNode.removeChild(addElement);
        }
        new_row.querySelector('.json-key input').setAttribute('name', new_name + "[_keyname_]");
        new_row.querySelector('.json-value input').setAttribute('name', new_name + "[_value_]");
        row.querySelector(':scope > .json-value').appendChild(new_row);

        e.preventDefault();
    }
});
