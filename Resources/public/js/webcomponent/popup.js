(function(tag) {
    customElements.define(tag,
        class Popup extends HTMLElement {
            constructor() {
                super();

                let node = document.getElementById(tag).content.cloneNode(true);

                const shadowRoot = this.attachShadow({mode: 'open'}).appendChild(node);

                this.attach();
                this.render();

                document.addEventListener('keyup', function(e) {
                    // There's no real value in checking if the popup is shown or not.
                    if ( e.keyCode == 27 ) {
                        this.hide();
                    }
                }.bind(this));

                document.body.addEventListener('click', function(e){
                    let openPopup = document.querySelector('eckinox-popup.visible:not(.opening)');

                    if (openPopup && !e.target.matches('eckinox-popup') && !e.target.closest('eckinox-popup') && !e.target.matches('#flash, #flash *')) {
                        openPopup.hide();
                    }
                });
            }

            attach() {
                let triggers = document.querySelectorAll('[eckinox-popup="' + this.getAttribute('name') + '"]');

                if ( triggers ) {
                    triggers.forEach(function(item) {
                        item.addEventListener('click', this.trigger_action.bind(this, item));
                    }.bind(this));
                }
            }

            render() {
                this.querySelectorAll('[action]:not(form)').forEach(function(element) {
                    element.addEventListener("click", this.action.bind(this, element), false);
                }.bind(this));

                this.hide();
            }

            action(element, e) {
                if ( ! element.attributes.action ) {
                    throw("A button was clicked onto which no action was bound.");
                }

                switch(element.attributes.action.value) {
                    case "cancel":
                    case "no":
                    case "close":
                    case "confirm":
                    case "yes":
                    case "ok":
                        this.hide();
                    break;
                }

                this.dispatchEvent(new CustomEvent('action:' + element.attributes.action.value));
                e.preventDefault();
            }

            hide() {
                this.classList.remove('visible', 'opening');
            }

            show() {
                if (!this.classList.contains('visible')) {
                    this.classList.add('opening');

                    setTimeout(function() {
                        this.classList.remove('opening');
                    }.bind(this), 300);
                }

                this.classList.add('visible');
            }

            trigger_action(item, e) {
                e.preventDefault();

                this.trigger = item;
                this.show();
            }

            get trigger() {
                return this._trigger;
            }

            set trigger(value) {
                return this._trigger = value;
            }

            get action_func() {
                return this._action_func;
            }

            set action_func(value) {
                return this._action_func = value;
            }

            _replace_vars(text, vars) {
                return text.match(/~\\{\\$(.*?)\\}~/i);
            }

            _get_slot(name) {
                return this.querySelector('[slot="' + name + '"]');
            }
        }
    )
})('eckinox-popup');
