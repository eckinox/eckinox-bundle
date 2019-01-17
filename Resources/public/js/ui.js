class BundleUI {
    static init() {
        BundleUI.initConfirmClick();
        BundleUI.initDropdownClose();
        BundleUI.initNavigationPanels();
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
}

// On load, initiate the basic UI listeners and behaviors
BundleUI.init();
