/*
 * Really simple timer / timeout object
 *
 * @author Dave Mc Nicoll
 * @version 2.0.0
 *
 */
class Hourglass {

    constructor(callback) {
        this.config = {
            timer: 1000,
            start: false,
            loop: true,
            run: false,  // If you need a defined number of code execution, set a number to run and loop to false.
            complete: function(){} // This complete callback function is called if the run exec number is reached.
        };

        this.object_id = null;
        this.counter = null;
        this.callback = callback !== undefined ? callback : function() {};

        if ( this.config.start ) {
            this.start();
        }
    }

    start(timeout) {
        let time = ( timeout !== undefined ? timeout : this.config.timer );

        if ( this.config.loop ) {
            if ( this.counter === null ) {
                this.counter = this.config.loop ? 0 : null
            }

            this.object_id = window.setInterval( this.call_interval.bind(this), time);
        }
        else {
            this.object_id = window.setTimeout( this.call_timeout.bind(this), time);
        }
    }

    restart(timeout) {
        this.clear();
        this.start(timeout);
    }

    stop() {
        if ( this.config.loop ) {
            this.object_id && window.clearInterval(this.object_id);
        }
        else {
            this.object_id && window.clearTimeout(this.object_id);
        }
    }

    clear() {
        this.stop();
        this.counter = null;
        this.object_id  = null;
    }

    call_timeout() {
        this.callback();
        this.clear();
    }

    call_interval() {
        this.callback();

        if ( this.counter !== null && ++this.counter === this.config.run ) {
            this.clear();
            this.config.complete !== undefined && this.config.complete();
        }
    }

    running() {
        return !! this.object_id;
    }

    get callback() {
        return this._callback;
    }

    set callback(value) {
        return this._callback = value;
    }

    get counter() {
        return this._counter;
    }

    set counter(value) {
        return this._counter = value;
    }

    get object_id() {
        return this._object_id;
    }

    set object_id(value) {
        return this._object_id = value;
    }
}
