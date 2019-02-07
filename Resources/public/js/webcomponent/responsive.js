/*
 * @author Dave Mc Nicoll
 * @version 2.0.0
 *
 * A web component allowing responsive-first development.
 *
 **/

config.webcomponent.responsive = {
    attribute: "re-width",
    class: "re-",
    custom_viewport: false,
    interval_viewport: {
        interval: 80,
        min: 320,
        max: 1600
    }
};

(function(tag) {
    var element_observer = new ElementObserver();

    customElements.define(tag,
        class Responsive extends Webcomponent {
            constructor() {
                super();

                this.init();
            }

            init() {
                this.viewport_list = this._viewport();
                this._update_element();
                element_observer.resize(this, this._update_element);
            }

            _update_element() {
                let idx = this._get_viewport_idx(this);

                this.setAttribute(this.config.attribute, this.viewport_list[idx]);

                if ( this.config.class ) {
                    for(let i in this.viewport_list) {
                        let cls = this.config.class + this.viewport_list[i];

                        ( parseInt(i) <= idx ) ? this.classList.add(cls) : this.classList.remove(cls);
                    }
                }
            }

            _viewport() {
                if ( this.config.custom_viewport.length ) {
                    this.config.custom_viewport.sort(function(a, b) {
                        return a.size - b.size;
                    });

                    return this.config.custom_viewport;
                }
                else {
                    let retval = [];

                    for(let i = this.config.interval_viewport.min;
                            i <= this.config.interval_viewport.max;
                            i = i + this.config.interval_viewport.interval ) {
                        retval.push(i);
                    }

                    return retval;
                }
            }

            _get_viewport_idx(element) {
                let width = element.getBoundingClientRect().width,
                    retval = 0;

                for( let i in this.viewport_list ) {
                    if ( this.viewport_list.hasOwnProperty(i) ) {
                        retval = parseInt(i);

                        if ( width <= this.viewport_list[i] ) {
                            retval--;
                            break;
                        }
                    }
                }

                return retval;
            }

            get viewport_list() {
                return this._viewport_list;
            }

            set viewport_list(value) {
                return this._viewport_list = value;
            }

            get element_observer() {
                return this._element_observer;
            }

            set element_observer(value) {
                return this._element_observer = value;
            }
        }
    )
})('eckinox-responsive');

