import { h, Component } from 'preact';
// import toReact from "../utils/html-to-react"

export default class ProductItem extends Component
{
    constructor(props) {
        super(props);

        this.state = {
            id: props.item.id||null,
            html: props.item.html||null,
        }
    }

    shouldComponentUpdate(nextProps, nextState)
    {
        return (nextProps.item.id !== nextState.id);
    }

    componentDidUpdate(prevProps, prevState)
    {
        if (this.props.item.id !== this.state.id) {
            this.setState({
                id: this.props.item.id||null,
                html: this.props.item.html||null,
            })
        }
    }


    render() {
        return <div className="item product "
                    data-product={this.state.id}
                    itemScope
                    itemType="http://schema.org/Product"
                    itemProp="itemListElement"
                    dangerouslySetInnerHTML={{__html: this.state.html}}>
        </div>
        // return toReact(this.state.html, {id: this.state.id});
    }
};