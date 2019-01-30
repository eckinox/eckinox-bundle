class BundleUI {
    static init() {
        BundleUI.initConfirmClick();
        BundleUI.initDropdownClose();
        BundleUI.initNavigationPanels();
        BundleUI.initFlashMessageDismissal();
        BundleUI.initTabWidgets();
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
        } else if (typeof content == 'Node') {
            element.appendChild(content);
        }
    }

    static showFlashMessage(type, message, timeout) {
        let wrapper = document.querySelector('#flash .flash-content');
        let html = `<div class="flash-message ${type}">
                        ${message}
                    </div>`;
        BundleUI.appendTo(wrapper, html);

        if (!wrapper.closest('#flash').classList.contains('open')) {
            wrapper.closest('#flash').classList.add('open');
        }
    }

    static clearFlashMessages() {
        let wrapper = document.querySelector('#flash .flash-content');
        wrapper.innerHTML = '';
    }
}

// On load, initiate the basic UI listeners and behaviors
BundleUI.init();
