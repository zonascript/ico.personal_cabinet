import { h, Component } from 'preact';
import ProductItem from '../components/ProductItem';

import _ from 'lodash';

export default class ListProducts extends Component
{
    constructor(props) {
        super(props);

        this.state = this.updateState(this.props);
    }

    updateState(props)
    {
        let state = {
            ids: [],
            items: [],
        };

        let ids = [];
        let diff = [];
        let sids = this.state.ids || [];

        if (props.items) {

            if (sids.length !== props.items.length) {
                ids = props.items.map((item)=>{ return item.id; });
            }

            if (sids.length === props.items.length) {
                ids = props.items.map((item)=>{ return item.id; });
                diff = _.difference(this.state.ids|| [], ids);
            }
        }

        if ((sids.length !== ids.length) || diff.length) {
            state = {
                ids: ids || [],
                items: props.items || []
            };

            this.setState(state);
        }

        return state;
    }

    componentWillMount()
    {
        this.updateState(this.props);
    }

    componentDidUpdate(prevProps, prevState)
    {
        this.updateState(this.props);

        $(document).trigger('lil.recheck');
    }

    renderProducts(items = [])
    {
        let result = [];

        if (items && items.length) {

            result = items.map((item)=>{
                return (<ProductItem item={item} key={'pi-' + item.id} /> );
            });

            return result;
        }
        return null;
    }

    render() {
        if (this.state.items && this.state.items.length) {
            return (
                <div className="product-items-wrap">
                    <div className="product-items tile-view">{this.renderProducts(this.state.items)}</div>
                </div>
            );
        }

        return null;
    }
};