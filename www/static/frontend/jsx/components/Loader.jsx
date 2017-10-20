import _ from "lodash";

export default class Loader
{
    constructor(options = {}) {
        this.loaders = 0;
        this.elements = {};
        this.options = _.extend({
            'timeout': 1000
        }, options);

        this.timer = null;
        this.timerDetach = null;

        this.init();
    }

    init(element) {
        this.elements['container'] = $(element || "body");

        if (
            !this.checkExist(this.elements['container'], '.loader-bg', 'bg')
            || !this.checkExist(this.elements['bg'], '.loader-wrapper', 'wrapper')
            || !this.checkExist(this.elements['wrapper'], '.loader-spinner', 'spinner')
            || !this.checkExist(this.elements['wrapper'], '.loader-container', 'content')
        ) {
            this.cleanUp();
            this.createElements();
        }
        else {
            this.loaders++;
        }
    }

    checkExist(container, cls = '.loader-bg', elKey = 'bg') {
        let el = container.find(cls);
        if (el.length) {
            this.elements[elKey] = el;
            return true;
        }

        return false;
    }

    createElements() {
        this.elements['bg'] = $('<div />').addClass('loader-bg');
        this.elements['wrapper'] = $('<div />').addClass('loader-wrapper');
        this.elements['spinner'] = $('<div />').addClass('loader-spinner');
        this.elements['content'] = $('<div />').addClass('loader-container');

        this.elements['bg'].append(this.elements['wrapper']);
        this.elements['wrapper'].append(this.elements['spinner']);
        this.elements['wrapper'].append(this.elements['content']);

        this._bind();
    }

    _bind() {
        this.elements['bg'].on('click load.detach', this.allDetach);
    }

    load(callback = null, max_time = 10000) {
        this.attach(max_time);

        if (callback) {
            if (typeof callback === 'function') {
                $
                    .when(callback())
                    .done((args)=>{ if (args) { this.detach(); } })
                    .fail(()=>{ this.detach(); })
                    .then(()=>{  });
            }
            else {
                $
                    .when(callback)
                    .done((args)=>{  })
                    .fail(()=>{  })
                    .then(()=>{ setTimeout(()=>{ this.detach(); }, this.options.timeout) });
            }
        }
    }

    attach(max_time = 10000) {
        if (!this.loaders) {
            this.timer = setTimeout(_=>{
                this.elements['container'].addClass('loading');
                this.elements['container'].append(this.elements['bg']);

                setTimeout(()=>{
                    this.elements['container'].addClass('loading-active');
                }, 20);

                clearTimeout(this.timerDetach);
                this.timerDetach = setTimeout(()=>{
                    this.detach();
                }, max_time);
            },this.options.timeout);

            this.loaders++;
        }
    }

    detach(callback = null) {
        this.loaders--;

        if (this.loaders <= 0) {
            clearTimeout(this.timer);

            setTimeout(()=>{
                this.elements['container'].removeClass('loading-active');
            }, this.options.timeout/2);

            setTimeout(()=>{
                this.elements['container'].removeClass('loading');
                this.elements['bg'].detach();
                if (typeof callback === 'function') {
                    callback();
                }
            }, this.options.timeout);

            if (this.loaders < 0) {
                this.loaders = 0;
            }
        }
    }

    allDetach() {
        this.loaders = 1;
        this.detach();
    }

    cleanUp() {
        this.elements['container'].removeClass('loading-active');
        this.elements['container'].find('.loader-bg').remove();
    }
}