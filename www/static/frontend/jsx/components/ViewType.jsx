import Storage from "../utils/storage";

export default class ViewType
{
    constructor() {
        this.elemets = {};
        this.options = {
            classes: {
                'container': '.product-items',
                'actions': '.action_block.view a'
            }
        };
        this.storage_key = 'cviewt';

        this.init();
    }

    init() {
        this.elemets['container'] = $(this.options.classes['container']);
        this.elemets['actions'] = $(this.options.classes['actions']);

        this._bind();
        this.setContainerClassess(this.get());
    }

    get() {
        return Storage.get(this.storage_key, this.defaultType());
    }

    set(value = null) {
        if (value && CategoryViewType.inTypes(value) ) {
            Storage.set(this.storage_key, value);
            this.setContainerClassess(value);
        }
    }

    setContainerClassess(type) {
        this.elemets['container'].removeClass(ViewType.getTypes().join(' '));
        this.elemets['container'].addClass(type);

        this.elemets['actions'].removeClass('active');
        this.elemets['actions'].each((n, el) => {
            let $el = $(el);
            if ($el.data('value') == type) {
                $el.addClass('active');
            }
        });

        $(window).trigger('resize');
    }

    _bind() {
        this.elemets['actions'].on('click', (e) => {
            e.preventDefault();
            let $this = $(e.target);
            let type = $this.data('value') || this.defaultType();
            
            this.set(type);
        });
    }

    static inTypes(value) {
        return (ViewType.getTypes().indexOf(value) !== -1);
    }

    defaultType(type)
    {
        if (!this.default || type) {
            if (type && ViewType.inTypes(type)) {
                this.default = type;
            }
            else {
                this.default = 'tile-view';
            }
        }

        return this.default;
    }

    static getTypes() {
        return ['tile-view', 'list-view'];
    }
}