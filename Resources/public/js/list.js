Dom.delegate('change', '.list .head .more .fields input[type="checkbox"][name^="field_"]', function(e) {
     let field = this.value,
         columns = document.querySelectorAll('.column.' + field),
         show = this.matches('input:checked');

     for (let column of columns) {
         if (show) {
             column.classList.remove('hide');
         } else {
             column.classList.add('hide');
         }
     }
});

// Edit shortcut when clicking on the row
Dom.delegate('click', '.list .rows .row *', function(e){
	if (this.matches('a, button, input, label') || this.closest('a, button, input, label')) {
		return;
	}

	let editLink = this.closest('.row').querySelector('.edit-link');
	if (editLink) {
		editLink.click();
    }
});

// Triggered when the user select all rows
Dom.delegate('change', '.list .head .column.checkbox input[type="checkbox"][name$="_select_all"]', function(e){
     let rows = document.querySelectorAll('.list .rows .row'),
         is_checked = this.matches('input:checked');

     for (let row of rows) {
         let checkbox = row.querySelector('.column.checkbox input[type="checkbox"]');

         checkbox.checked = is_checked;
         row.classList.toggle("active", is_checked);
     }
});

// Triggered when the user select one row
Dom.delegate('change', '.list .rows .row .column.checkbox input[type="checkbox"]', function(e){
    let checkbox = this,
        row = checkbox.parentNode.parentNode,
        is_checked = this.matches('input:checked');

    checkbox.checked = is_checked;
    row.classList.toggle("active", is_checked);

    // Check if all rows are selected
    let rows = document.querySelectorAll('.list .rows .row'),
        active_rows = document.querySelectorAll('.list .rows .row.active'),
        select_all_checkbox = document.querySelector('.list .head .column.checkbox input[type="checkbox"][name$="_select_all"]');

    if (select_all_checkbox) {
        select_all_checkbox.checked = ( rows.length === active_rows.length );
    }
});

// Triggered when the user clears the search
Dom.delegate('click', '.list .head.search .column.cancel i', function(e){
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
Dom.delegate('change', '.list .row.head.search .column select', function(e){
    this.closest('form').submit();
});

// Setup pagination inputs
Dom.delegate('keydown', '.pagination .page-input-wrapper input', function(e){
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
