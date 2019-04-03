document.addEventListener('change', function(e) {
     if (!e.target.matches('.list .head .more .fields input[type="checkbox"][name^="field_"]')) {
         return;
     }

     let field = e.target.value,
         columns = document.querySelectorAll('.column.' + field),
         show = e.target.matches('input:checked');

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

// Triggered when the user select all rows
document.addEventListener('change', function(e) {
     if (!e.target.matches('.list .head .column.checkbox input[type="checkbox"][name$="_select_all"]')) {
         return;
     }

     let rows = document.querySelectorAll('.list .rows .row'),
         is_checked = e.target.matches('input:checked');

     for (let row of rows) {
         let checkbox = row.querySelector('.column.checkbox input[type="checkbox"]');

         checkbox.checked = is_checked;
         row.classList.toggle("active", is_checked);
     }
});

// Triggered when the user select one row
document.addEventListener('change', function(e) {
    if (!e.target.matches('.list .rows .row .column.checkbox input[type="checkbox"]')) {
        return;
    }

    let checkbox = e.target,
        row = checkbox.parentNode.parentNode,
        is_checked = e.target.matches('input:checked');

    checkbox.checked = is_checked;
    row.classList.toggle("active", is_checked);

    // Check if all rows are selected
    let rows = document.querySelectorAll('.list .rows .row'),
        active_rows = document.querySelectorAll('.list .rows .row.active'),
        select_all_checkbox = document.querySelector('.list .head .column.checkbox input[type="checkbox"][name$="_select_all"]');

    select_all_checkbox.checked = ( rows.length === active_rows.length );

});

// Triggered when the user clears the search
document.addEventListener('click', function(e) {
    if (!e.target.matches('.list .head.search .column.cancel i')) {
        return;
    }

    let inputs = document.querySelectorAll('.list .head.search .column input'),
        selects = document.querySelectorAll('.list .head.search select'),
        form = document.querySelector('form[name$="-list-form"]');

    for(let input of inputs) {
        input.value = '';
    }

    for(let select of selects) {
        select.value = '';
    }

    form.submit();
});

// Auto-submits the search filters when a select filter is changed


// Triggered when the user select one row
Dom.delegate('change', '.list .row.head.search .column select', function(e){
    this.closest('form').submit();
});

// Setup pagination inputs
document.addEventListener('keydown', function(e) {
    if (!e.target.matches('.pagination .page-input-wrapper input')) {
        return;
    }

    if (e.which != 13) {
        return;
    }

    let input = e.target;
    let page = parseInt(input.value);
    let url = input.getAttribute('url-template').replace('/0', '/' + page);

    window.location.assign(url);
});

// Makes paginations work when there's an active search
let listingSearchNodes = document.querySelectorAll('.list .row.search');
for (let searchNode of listingSearchNodes) {
    let searchForm = searchNode.closest('form');
    for (let paginationLink of searchForm.parentNode.querySelectorAll('.pagination a')) {
        paginationLink.addEventListener('click', function(e) {
            e.preventDefault();
            searchForm.action = paginationLink.getAttribute('href');
            searchForm.submit();
        });
    }
}
