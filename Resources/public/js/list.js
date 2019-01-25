document.addEventListener('change', function(e) {
     if (!e.target.matches('.list .head .more .fields input[type="checkbox"][name^="field_"]')) {
         return;
     }

     let field = e.target.value;
     let columns = document.querySelectorAll('.column.' + field);
     let show = e.target.matches('input:checked');

     for (let column of columns) {
         if (show) {
             column.classList.remove('hide');
         } else {
             column.classList.add('hide');
         }
     }
});

// Edit shortcut when clicking on the row
document.addEventListener('click', function(e){
	if (e.target.matches('.list .rows .row *')) {
		if (e.target.matches('a, button, input, label') || e.target.closest('a, button, input, label')) {
			return;
    	}

    	let editLink = e.target.closest('.row').querySelector('.edit-link');
		if (editLink) {
			editLink.click();
        }
    }
});

// Edit shortcut when clicking on the row
document.addEventListener('change', function(e){
	if (e.target.matches('.row.head.fields .column.checkbox input')) {
        let masterCheckbox = e.target;
        let checkAll = masterCheckbox.matches(':checked');

        for (let checkbox of masterCheckbox.closest('.list').querySelectorAll('.rows .row input[name="ids[]"]')) {
            checkbox.checked = checkAll;
        }
    }
});
