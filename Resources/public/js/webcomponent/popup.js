(function(tag) {
    customElements.define(tag,
        class Popup extends HTMLElement {
            constructor() {
                super();

                let node = document.getElementById(tag).content.cloneNode(true);

                const shadowRoot = this.attachShadow({mode: 'open'}).appendChild(node);

                this.attach();
                this.render();
            }

            attach() {
                let triggers = document.querySelectorAll('[eckinox-popup]');

                if ( triggers ) {
                    triggers.forEach(function(item) {
                        item.addEventListener('click', this.trigger_action.bind(this));
                    }.bind(this));
                }
            }

            render() {
                let btn = this._get_slot("buttons");

                if ( btn ) {
                    this.querySelectorAll('[action]').forEach(function(element) {
                        element.addEventListener("click", this.action.bind(this, element), false);
                    }.bind(this));
                }

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
                        this.hide();
                        break;

                    case "confirm":
                    case "yes":
                    case "ok":
                        this.hide();

                        //element.removeEventListener('click', this.trigger_action.bind(this));

                        break;
                }

                this.dispatchEvent(new CustomEvent('action:' + element.attributes.action.value));

            }

            hide() {
//                this.style.display = "none";
                this.classList.remove('visible');
            }

            show() {
//                this.style.display = "block";
                this.classList.add('visible');
            }

            trigger_action(e) {
                e.preventDefault();

                this.trigger = e.target;
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
