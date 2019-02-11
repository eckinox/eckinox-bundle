/*
 * @author Dave Mc Nicoll
 * @version 1.0.0
 *
 *  Analysis elements entering or quitting the DOM, triggering events whenever
 *  a matching element is found.
 *
 **/

const ACTION_ADDED   = 0,
      ACTION_REMOVED = 1,
      ACTION_RESIZED = 2,
      MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

class ElementObserver {

    constructor() {
        this.config = {
            resize: {
                timer: 200
            }
        };
        
        this.observer = null;
        this.listeners = [ [] , [] ];
    }

    added(selector, callback) {
        this._observe(selector, callback, ACTION_ADDED);
    }

    removed(selector, callback) {
        this._observe(selector, callback, ACTION_REMOVED);
    }

    /* a little hacky function, awaiting the ResizeObserver specs to be ready */
    resize(element, callback) {
        var width = 0;

        if ( ! this.hourglass ) {
            element.dataset.element_width = element.getBoundingClientRect().width;

            this.hourglass = new Hourglass(function() {
                this.resizing.forEach(function(item) {
                    width = item.element.getBoundingClientRect().width;

                    if ( item.element.dataset !== width ) {
                        item.element.dataset.element_width = width;
                        item.callback.call(item.element, width);
                    }
                });
            }.bind(this), {
                start    : false,
                repeat   : false,
                timer    : this.config.resize.timer
            });

            window.addEventListener('resize', function() {
                this.hourglass.restart();
            }.bind(this));
        }

        this.resizing = this.resizing || [];

        this.resizing.push({
            element: element,
            callback: callback
        });
    }

    _observe(selector, callback, action) {
        this.listeners[action].push({
            selector : selector,
            callback : callback
        });

        this._register_observer();
    }

    _analyze_element(element, action) {
        let split_selector = [];

        for (let j = 0, l1 = this.listeners[action].length; j < l1; j++) {
            split_selector = this.listeners[action][j].selector.split(' ');

            if ( split_selector.length > 1 ) {
                console.log("@todo!", split_selector);
            }
            else {
                if ( $element.is( this.listeners[action][j].selector ) ) {
                    this.listeners[action][j].callback.call(element, element);
                }
            }
        }
    }

    _analyze_added_element() {
        return this._analyze_element(this, ACTION_ADDED);
    }

    _analyze_removed_element() {
        return this._analyze_element(this, ACTION_REMOVED);
    }

    _observe_element(e) {
        for(var i = 0; i < e.length; i++) {
            if (e[i].type === 'childList') {
                console.error("THIS PART IS NOT TRANSLATED INTO PURE JS AS OF NOW ... SOME THINKING NEED TO BE DONE FIRST !");
//                $(e[i].addedNodes).each(this.analyze_added_element);
//                $(e[i].removedNodes).each(this.analyze_removed_element);
            }
        }
    }

    _register_observer() {
        if ( this.observer || ( this.observer = new MutationObserver(this._observe_element) ) ) {
            this.observer.observe(document.documentElement, {
                subtree: true,
                childList: true
            });
        }
    }

    set observer(value) {
        return this._observer = value;
    }

    get observer() {
        return this._observer;
    }

    set listeners(value) {
        return this._listeners = value;
    }

    get listeners() {
        return this._listeners;
    }

    get hourglass() {
        return this._hourglass;
    }

    set hourglass(value) {
        return this._hourglass = value;
    }

    get resizing() {
        return this._resizing;
    }

    set resizing(value) {
        return this._resizing = value;
    }
}
