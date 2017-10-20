import 'intersection-observer';

export default class LazyImageLoad
{
    constructor(elements = '.lazy-img, [data-background]') {
        this.attached = [];
        this.timer = null;
        this.intervalSearch = null;
        this.intervalLoad = null;
        this.timeoutToFullLoad = null;
        this.observer = null;
        this.inLoad = 0;
        this.maxLoad = 3;
        this.stack = [];

        this.setObserver();
        this.init(elements);
    }

    init(elements) {
        this.attached.push(elements);

        this._bind();
        this.search();
    }

    setObserver()
    {
        this.observer = new IntersectionObserver(entries => {
            for (let i = 0, len = entries.length; i < len; i++) {
                if (entries[i].intersectionRatio <= 0) continue;

                this.load(entries[i].target);
            }
        },
        {
            threshold: .3
        });

        this.observer.POLL_INTERVAL = 50;
    }

    /**
     *
     * @param target Element
     */
    load(target)
    {
        let $target = $(target);

        $target.removeClass('lazy-img');

        if ($target.attr('data-background')) {
            this.stack.push(()=>{
                let background = $target.attr('data-background');

                $target.attr('data-background', null);
                $target.addClass('lazy-bg');

                this.onLoad(background,
                    ()=>{ $target.css({'background-image': 'url('+background+')'}); },
                    ()=>{ $target.addClass('lazy-bg-loaded'); }
                );
            });

        }

        if ($target.attr('data-original')) {
            this.stack.push(()=>{
                let original = $target.attr('data-original');
                let hasUpdate = ($target.src !== original);

                if (hasUpdate) {
                    $target.attr('src', '');
                    $target.attr('data-original', null);

                    this.onLoad(original,
                        ()=>{ $target.attr('src', original); },
                        ()=>{ $target.addClass('lazy-loaded');
                            if (typeof($target.attr('usemap')) !== 'undefined' && $.fn.rwdImageMaps) {
                                $target.rwdImageMaps();
                            }
                        }
                    );
                }
            });
        }

        this.observer.unobserve(target.element);
        $(document).trigger('lil.tick');
    }

    onLoad(image, call1, call2)
    {
        let element = document.createElement('img');

        element.onload = () => {
            setTimeout(()=>{call1()}, 20);
            setTimeout(()=>{call2()}, 200);

            this.inLoad--;
            element.remove();
        };

        this.inLoad++;
        element.src = image;
    }

    attach(elements) {
        this.attached.push(elements);
    }

    _bind() {
        $([document,window]).on('resize', ()=>{ this.observe() });
        $(document).on('lil.observe', ()=>{ this.observe(); });
        $(document).on('lil.partial', ()=>{ this.fullLoad(); });
        $(document).on('lil.full', ()=>{ this.fullLoad(true); });
        $(document).on('lil.tick', ()=>{
            this.runTimer();
        });

        this.intervalSearch = setInterval(()=>{
            $(document).trigger('lil.observe');
        }, 3000);
    }

    runTimer(load_all = false, time_out = 50) {
        if (this.stack.length && !this.intervalLoad) {
            this.intervalLoad = setInterval(()=>{
                this.each(load_all);

                if (!this.stack.length) {
                    clearTimeout(this.intervalLoad);
                    this.intervalLoad = null;
                }
            }, time_out);
        }
    }

    toObserver(items = [])
    {
        if (items.length)
        {
            for (let i = 0, len = items.length; i < len; i++) {
                this.observer.observe(items[i]);
            }
        }
    }

    toLoad(items = [], full = false)
    {
        clearTimeout(this.timeoutToFullLoad);

        if ((this.inLoad < this.maxLoad) && items.length)
        {
            for (let i = 0, len = items.length; i < len && (full || i <= this.maxLoad) && this.inLoad < this.maxLoad; i++) {
                this.load((i % 2) ? items.pop() : items.shift());
            }
        }

        if (items.length) {
            this.timeoutToFullLoad = setTimeout(()=>{
                $(document).trigger('lil.partial');
            }, 1000);
        }
    }

    observe()
    {
        let items = this.search();
        if (items.length)
        {
            this.toObserver(items);

            setTimeout(()=>{
                if (this.inLoad === 0) {
                    $(document).trigger('lil.partial');
                }
            }, 3000)
        }
    }

    fullLoad(full = false)
    {
        this.toLoad(this.search(), full)
    }

    search()
    {
        let query = this.attached.join(',');
        let items = document.querySelectorAll(query);
        if (items.length) {
            return Array.prototype.slice.call(items);
        }
        return [];
    }

    each(load_all = false)
    {
        while (this.stack.length) {
            if (this.maxLoad >= this.inLoad || load_all) {
                ((this.inLoad % 2) ? this.stack.pop():this.stack.shift())();
            }
            else {
                break;
            }
        }
    }
}
