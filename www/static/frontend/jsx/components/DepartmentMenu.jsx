import MouseSpeed from './MouseSpeed';

export default class DepartmentMenu
{
    constructor() {
        this.timers = {};
        this.elemets = {};
        this.options = {
            hoverDelay: 1500,
            classes: {
                'main-button': '.category-menu',
                'menu-wrapper': '.category-menu-list-wrapper',
                'menu-row': '.category-menu-list-row',
                'menu-container': '.category-menu-list-container',
                'menu-item': '.category-menu-list-container .item-container',
                'submenu-container': '.submenu-container',
            }
        };

        this.ms = new MouseSpeed();
        this.hasTouch = this.checkTouch();

        this.init();
    }

    init() {
        this.elemets['button'] = $(this.options.classes['main-button']);
        this.elemets['container'] = $(this.options.classes['menu-container']);
        this.elemets['wrapper'] = $(this.options.classes['menu-wrapper']);
        this.elemets['row'] = $(this.options.classes['menu-row']);
        this.elemets['submenu-container'] = $(this.options.classes['submenu-container']);
        this.elemets['items'] = $(this.options.classes['menu-item']);

        this._bind();
    }

    _bind() {
        let self = this;

        this.elemets['button'].on('mouseenter touchstart', (e) => {
            clearTimeout(this.timers['_hide']);
            this._show_menu();
        });

        this.elemets['container'].on('mouseenter touchstart', (e) => {
            clearTimeout(this.timers['_hide']);
        });

        this.elemets['row'].on('click', (e)=>{
            let str = this.options.classes['menu-row'].substr(1);

            if ($(e.target).hasClass(str)) {
                clearTimeout(this.timers['_hide']);
                this._hide();
            }
        });


        this.elemets['items'].on('click', function(e) {

            if ($(this).hasClass('has-child') || self.checkTouch()) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        this.elemets['button'].on('mouseleave', (e) => {
            this.timers['_hide'] = setTimeout(()=> {
                this._hide();
            }, this.options.hoverDelay)
        });

        this.elemets['container'].on('mouseleave', (e) => {
            this.timers['_hide'] = setTimeout(()=> {
                this._hide();
            }, this.options.hoverDelay)
        });

        this.elemets['items'].on('mouseenter touchstart', (e) => {
            this._hide_items();

            let $target = $(e.target);
            if (!$target.hasClass(this.options.classes['menu-item'])) {
                $target = $target.closest(this.options.classes['menu-item']);
            }

            $('#' + $target.data('hover-toggle')).removeClass('hide');
            this.elemets['container'].addClass('submenu-active');
        });

        $(document).on('click:shadow', (e)=>{
            clearTimeout(this.timers['_hide']);
            this._hide();
        });
    }

    checkTouch() {
        return (window.whatInput.ask('loose') === 'touch' || window.whatInput.ask() === 'touch' );
    }

    _show_menu() {
        this.elemets.wrapper.removeClass('hide');
        this.elemets.wrapper.addClass('is-active');
        this.elemets.button.addClass('is-active');

        $(document).trigger('show:dm');
    }

    _hide_items() {
        for (let i = 0; i < this.elemets['items'].length; i++)
        {
            let tid =  $(this.elemets['items'][i]).data('hover-toggle');
            $('#' + tid).addClass('hide');
        }
    }

    _hide() {
        this.elemets.wrapper.addClass('hide');
        this.elemets.wrapper.removeClass('is-active');
        this.elemets.button.removeClass('is-active');
        this.elemets.container.removeClass('submenu-active');
        this._hide_items();

        $(document).trigger('hide:dm');
    }
}
