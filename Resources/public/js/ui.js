class BundleUI {
    static init() {
        window.addEventListener('load', function(){
            BundleUI.initConfirmClick();
            BundleUI.initDropdownClose();
            BundleUI.initNavigationPanels();
            BundleUI.initFlashMessageDismissal();
            BundleUI.initTabWidgets();
            BundleUI.initGroupedCheckboxes();
        }, false);
    }

    // Confirms clicks on [confirm-click] elements with a custom message defined in the attribute
    // If cancelled, the click is simply cancelled.
    static initConfirmClick() {
        document.addEventListener('click', function(e){
            if (e.target.matches('[confirm-click]') && !confirm(e.target.getAttribute('confirm-click'))) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Allows the dropdown elements to be closed when the user clicks elsewhere
    static initDropdownClose() {
        document.addEventListener('click', function(e){
            let openDropdown = document.querySelector('.actions-button input[type=checkbox]:checked + ul.dropdown');

            if (openDropdown && (!e.target.closest('.actions-button') || (e.target.closest('.actions-button') != openDropdown.closest('.actions-button')))) {
                if (openDropdown.previousElementSibling) {
                    openDropdown.previousElementSibling.checked = false;
                }
            }
        });
    }

    // Allows the opening of sub-panels in the main navigation
    static initNavigationPanels() {
        document.addEventListener('click', function(e) {
            let item = e.target;

            if (e.target.matches('#main-navigation .link-item') || (item = e.target.closest('#main-navigation .link-item'))) {
                let subnavigation = document.querySelector('#main-subnavigation');
                let panelName = item.getAttribute('data-panel');
                let panel = subnavigation.querySelector('.panel[data-panel="' + panelName + '"]');

                if (panelName) {
                    let isOpen = panel.classList.contains('open');
                    let openPanel = subnavigation.querySelector('.panel.open');

                    // Close the panel that is currently open
                    if (openPanel) {
                        openPanel.classList.remove('open');
                        document.querySelector('#main-navigation .link-item.active').classList.remove('active');

                        if (isOpen) {
                            subnavigation.classList.remove('open');
                        }
                    }

                    // Open the desired panel
                    if (!isOpen) {
                        item.classList.add('active');
                        panel.classList.add('open');

                        if (!openPanel) {
                            subnavigation.classList.add('open');
                        }
                    }

                    setTimeout(function(){
                        window.dispatchEvent(new Event('resize'));
                    }, 300);
                }
            }
        });
    }

    static initFlashMessageDismissal() {
        document.addEventListener('click', function(e) {
            let item = e.target;

            if (e.target.matches('.flash-message') || (item = e.target.closest('.flash-message'))) {
                item.classList.add('fade-out', 'slide-down');
                setTimeout(function(){
                    let parent = item.parentNode;
                    parent.removeChild(item);

                    if (!parent.childNodes.length) {
                        parent.closest('#flash').classList.remove('open');
                    }
                }, 250);
            }
        });
    }

    static initTabWidgets() {
        document.addEventListener('click', function(e){
        	if (!e.target.matches('[widget-tabs] [widget-tab], [widget-tabs] [widget-tab] *')) {
                return;
            }

            let tabElement = e.target.matches('[widget-tabs] [widget-tab]') ? e.target : e.target.closest('[widget-tabs] [widget-tab]');

            if (!tabElement) {
                return;
            }

            let tabCategory = tabElement.closest('[widget-tabs]').getAttribute('widget-tabs');
            let tab = tabElement.getAttribute('widget-tab');

            for (let contentElement of document.querySelectorAll('[widget-tabs-content="' + tabCategory + '"] [widget-tab], [widget-tabs="' + tabCategory + '"] [widget-tab]')) {
                contentElement.classList.toggle('active', contentElement.getAttribute('widget-tab') == tab);
            }
        });

        // Open a tab when we have a hash in the url
        let hash = window.location.hash;

        if(hash) {
            let tabName = hash.replace('#', ''),
                tab = document.querySelector('.widget-tabs-element [widget-tab="' + tabName + '"]');

            tab.click();

            // Remove the hash to keep the URL clean :)
            history.pushState("", document.title, window.location.pathname + window.location.search);
        }
    }

    static initGroupedCheckboxes() {
        document.querySelector('body').addEventListener('click', function(e){
        	if (!e.target.matches('.field-wrapper .group .group-title')) {
        		return;
            }

        	e.preventDefault();

        	let groupTitle = e.target;
        	let checkboxes = groupTitle.parentNode.querySelectorAll('.choices input[type="checkbox"]');
        	let alreadyChecked = true;

        	for (let checkbox of checkboxes) {
        		alreadyChecked = alreadyChecked && checkbox.checked;
            }

        	for (let checkbox of checkboxes) {
        		checkbox.checked = !alreadyChecked;
            }
        });
    }

    static empty(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    static createNodeFromString(html) {
        let div = document.createElement('div');
        div.innerHTML = html.trim();
        return div.firstChild;
    }

    static createNodesFromString(html) {
        let div = document.createElement('div');
        div.innerHTML = html.trim();
        return div.childNodes;
    }

    static appendTo(element, content) {
        if (typeof content == 'string') {
            content = BundleUI.createNodesFromString(content);
            for (let i = 0; i < content.length; i++) {
                element.appendChild(content[i]);
            }
        } else if (['Node', 'object'].indexOf(typeof content) != -1) {
            element.appendChild(content);
        }
    }

    static insertAfter(element, referenceElement) {
        referenceElement.parentNode.insertBefore(element, referenceElement.nextElementSibling);
    }

    static showFlashMessage(type, message, timeout) {
        let wrapper = document.querySelector('#flash .flash-content');
        let html = `<div class="flash-message ${type}">
                        ${message}
                    </div>`;
        let node = BundleUI.createNodeFromString(html);
        BundleUI.appendTo(wrapper, node);

        if (!wrapper.closest('#flash').classList.contains('open')) {
            wrapper.closest('#flash').classList.add('open');
        }

        if (typeof timeout != 'undefined') {
            setTimeout(function(){
                if (node) {
                    node.click();
                }
            }, timeout);
        }
    }

    static clearFlashMessages() {
        let wrapper = document.querySelector('#flash .flash-content');
        wrapper.innerHTML = '';
    }

    static initAucompleteInput(hashId, settings) {
        if (typeof settings == 'undefined') {
            settings = JSON.parse(document.querySelector('#autocomplete_' + hashId).getAttribute('autocomplete-settings'));
        }

        let vars = {};
        vars['xhr_' + hashId] = null;
        vars['inputSelector_' + hashId] = 'input#autocomplete_' + hashId;

        new autoComplete({
            selector: vars['inputSelector_' + hashId],
            minChars: 2,
            source: function(term, response){
                try { xhr.abort(); } catch(e){}

                let data = new FormData();
                for (let key in settings) {
                    data.append(key, typeof settings[key] == 'object' ? JSON.stringify(settings[key]) : settings[key]);
                }
                data.append('query', term);

                vars['xhr_' + hashId] = new XMLHttpRequest();
                vars['xhr_' + hashId].open('POST', '/ajax/get/autocomplete-entities', true);

                vars['xhr_' + hashId].onload = function() {
                    if (vars['xhr_' + hashId].status >= 200 && vars['xhr_' + hashId].status < 400) {
                        response(JSON.parse(vars['xhr_' + hashId].responseText));
                    } else {
                        response();
                    }
                };

                vars['xhr_' + hashId].onerror = function() {
                    response();
                };

                vars['xhr_' + hashId].send(data);
            },
            renderItem: function(item, search) {
                search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                let re = new RegExp("(" + search.split(' ').join('|') + ")", "gi");
                let label = item.label + (typeof settings.key != 'undefined' && settings.key != 'id' ? (" (" + item.key + ")") : '');
                let element = document.createElement('div');

                element.classList.add('autocomplete-suggestion');
                element.setAttribute('data-val', label);
                element.setAttribute('data-key', item.key);
                element.setAttribute('data-label', item.label);
                element.setAttribute('data-obj', JSON.stringify(item.object));
                element.innerHTML = label.replace(re, "<b>$1</b>");

                return element.outerHTML;
            },
            onSelect: function(e, term, item) {
                let event = new CustomEvent('autocomplete', { detail: JSON.parse(item.getAttribute('data-obj')) });
                document.querySelector(vars['inputSelector_' + hashId]).dispatchEvent(event);
            }
        });

        document.querySelector(vars['inputSelector_' + hashId]).addEventListener('keypress', function(e) {
            if (e.which == 13){
                e.preventDefault();
            }
        });
    }
}

// On load, initiate the basic UI listeners and behaviors
BundleUI.init();
