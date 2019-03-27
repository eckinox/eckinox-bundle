(function(tag) {
    customElements.define(tag,
        class Textarea extends HTMLElement {
            constructor() {
                super();

                this.config = {
                    content : {
                        input: ".input",
                        toolbar: ".toolbar",
                        wrapper: ".wrapper",
                        boilerplate: ".content"
                    },

                    base_rows: 1.6,

                    dom : {
                        html : {
                            replace_tag : {
                                i : "em",
                                b : "strong",
                                pre : "div"
                            }
                        }
                    },

                    copy_css : [
                         'font', 'font-size', 'color', 'background', 'outline'
                     ]
                };

                const shadowRoot = this.attachShadow({mode: 'open'})
                    .appendChild(document.getElementById(tag).content.cloneNode(true));

                this.wrapper = this.shadowRoot.querySelector(this.config.content.wrapper);
                this.content = this.shadowRoot.querySelector(this.config.content.boilerplate);
                this.toolbar = this.shadowRoot.querySelector(this.config.content.toolbar);

                if ( ! this.textarea_init() ) {
                    return;
                }

                this.toolbar_init();
                this.render();
            }

            render() {
                document.execCommand("DefaultParagraphSeparator", false, "div");
                this.content.innerHTML = this.textarea.value.trim();

                this.content.addEventListener("input", function(e, d) {
                    this.content.querySelectorAll('[style]').forEach(function(element) {
                        element.removeAttribute('style');
                    });

                    this.content.querySelectorAll('[class]').forEach(function(element) {
                        element.removeAttribute('class');
                    });

                    this.textarea.value = this.content.innerHTML;
                    this.content.dispatchEvent(new Event("change"));
                }.bind(this));
            }

            action_align(value) {
                switch(value) {
                    case "left" :
                        return document.execCommand('justifyLeft',false,null);
                    case "center" :
                        return document.execCommand('justifyCenter',false,null);
                    case "right" :
                        return document.execCommand('justifyRight',false,null);
                    case "justify" :
                        return document.execCommand('justifyFull',false,null);
                }
            }

            action_bold(value, $content) {
                return document.execCommand('bold', false, null);
            }

            action_italic(value, $content) {
                return document.execCommand('italic', false, null);
            }

            action_underline() {
                return document.execCommand('underline',false, null);
            }

            action_strike() {
                return document.execCommand('strikeThrough',false, null);
            }

            action_ul() {
                return document.execCommand('insertUnorderedList',false, null);
            }

            action_ol() {
                return document.execCommand('insertOrderedList',false, null);
            }

            action_undo() {
                return document.execCommand('undo', false, null);
            }

            action_redo() {
                return document.execCommand('redo', false, null);
            }

            action_forecolor(color) {
                this.toolbar.querySelector('.font-color svg').style.color = color;
                return document.execCommand('forecolor', false, color);
            }

            action_link() {

            }

            action_image() {

            }

            action_document() {

            }

            action_tag(tag) {
                return document.execCommand('formatBlock', false, '<'+tag+'>');
            }

            toolbar_init() {
                let action = this.getAttribute('toolbar');

                if ( action !== null ) {
                    this.toolbar.setAttribute('action', action);
                }

                this.toolbar.querySelectorAll('[data-action]:not(.font-color)').forEach(function(item){
                    item.addEventListener('click', function(e) {
                        e.preventDefault();

                        return this["action_" + item.getAttribute('data-action')].call(this, item.getAttribute('data-value'), this.content);
                    }.bind(this));
                }.bind(this));

                this.toolbar.querySelectorAll('.font-color').forEach(function(item) {
                    item.querySelector('input').addEventListener('change', function(e) {
                        return this.action_forecolor.call(this, e.target.value);
                    }.bind(this));
                }.bind(this));
            }

            textarea_init() {
                if ( ! (this.textarea = this.querySelector("textarea") ) ) {
                    console.error("You should have a <textarea> element whitin a container with slot='input'");
                    return;
                }

                if ( this.textarea.getAttribute('readonly') ) {
                    this.wrapper.classList.add("readonly");
                    this.content.removeAttribute('contenteditable');
                }

                let rows,
                    placeholder,
                    format;

                if ( rows = this.textarea.getAttribute('rows') ) {
                    rows = parseInt(rows);
                    this.content.style.minHeight = ( rows * this.config.base_rows ) + "em";
                }

                if ( placeholder = this.textarea.getAttribute('placeholder') ) {
                    this.content.setAttribute('placeholder', placeholder);
                }

                if ( format = this.getAttribute('format') ) {
                    this.wrapper.setAttribute('format', format);
                }

                return true;
            }

            get content() {
                return this._content;
            }

            set content(value) {
                return this._content = value;
            }

            get toolbar() {
                return this._toolbar;
            }

            set toolbar(value) {
                return this._toolbar = value;
            }

            get textarea() {
                return this._textarea;
            }

            set textarea(value) {
                return this._textarea = value;
            }

            get element() {
                return this._element;
            }

            set element(value) {
                return this._element = value;
            }

            get wrapper() {
                return this._wrapper;
            }

            set wrapper(value) {
                return this._wrapper = value;
            }
        }
    )
})('ui-textarea');
