import { h, Component } from 'preact';
import linkState from 'linkstate';

import ListProducts from '../components/ListProducts';

import objToUri from "../utils/objToUri";
import isTouch from "../utils/isTouch";
import sendAnalytics from "../utils/sendAnalytics";

export default class Search extends Component
{
    constructor() {
        super();

        this.timeout = 400;
        this.lastQ = '';

        this.timers = {
            suggest: null,
            search: null
        };

        this.counters = {
            suggest: 0,
            search: 0
        };

        this.state = {
            q: '',
            loading: false,
            errors: '',
            classes: [],
            search_result: [],
            p: 0,
            p_max: 0,
            suggestions: this.getDefaultSuggestions(),
        };
    }

    getDefaultSuggestions()
    {
        return {
            suggests: [],
            products: [],
            categories: [],
        };
    }

    componentDidMount()
    {
        if (this.props.q) {
            this.setState({q: this.props.q});
        }

        if (this.state.q) {
            this.getSearchResult();
        }
    }

    componentDidUpdate(prevProps, prevState)
    {
        $(document).trigger('lil.observe');
    }

    componentWillUpdate(nextProps, nextState)
    {
        if (nextProps.q) {
            this.state.q = nextProps.q;
        }

        if (nextState.q && this.lastQ != nextState.q) {
            this.getSuggestions(nextState.q);
        }
        else if (!nextState.q) {
            this.lastQ = '';
            nextState.search_result = [];
            nextState.classes = [];
            nextState.suggestions = this.getDefaultSuggestions();
        }

        return true;
    }

    submitHandler(e)
    {
        e.preventDefault();

        if (isTouch()) {
            let $form = null;

            if (e.target.tagName !== 'form') {
                $form = $(e.target).closest('form');
            }
            else {
                $form = $(e.target);
            }

            $form.find('input').blur();
        }

        this.setState({
            loading: true,
        });

        this.getSearchResult();
    }

    suggestionHandler(str)
    {
        this.setState({
            q: str,
            loading: true,
        });
        this.getSearchResult();
    }

    paginateHandler(e)
    {
        e.preventDefault();

        this.getSearchResult(this.state.p + 1);
    }

    getSearchResult(p=1)
    {
        let str = this.state.q;
        let pp = this.props.products_pp || 20;
        if (str && str.length >= 3) {

            this.stopPrevSuggestion();
            this.setState({
                loading: true,
            });

            $.ajax((this.props.search_url || this.props.action), {
                dataType: "json",
                data: {'q': str, 'p': p, 'pp': pp },
                success : (data, status, xhr)=>{
                    if (data.products) {
                        let sr = [];
                        let hState = {'q': str, 'p':p};
                        let uri = this.props.action + '?' + objToUri(hState);

                        if (p === 1) {
                            sr = data.products;
                        }
                        else {
                            sr = this.state.search_result.concat(data.products);
                        }

                        this.setState({
                            search_result: sr,
                            p: p,
                            p_max: Math.ceil(data.count / pp),
                            loading: false,
                        });
                        this.startClassesAnimation();


                        history.pushState(hState, "Search: " + str, uri);
                        sendAnalytics({hitType: 'pageview', location: uri});
                    }
                    else if (xhr.status !== 278) {
                        this.setState({
                            search_result: [],
                            classes: '',
                        });

                        window.addFlashMessage('Nothing found. Try again!', 'error');
                    }

                    this.setState({loading: false});
                },
                error: (xhr, type, message) => {
                    this.setState({loading: false});
                    if (xhr.status === 278) {
                        window.addFlashMessage('You will be moved to the goods.', 'info');
                    }
                    else if (xhr.status !== 278) {
                        console.error(type, message);
                        window.addFlashMessage('An error has occurred. Please try again later.', 'error');
                    }
                }
            });
        }
    }

    startClassesAnimation()
    {
        this.stopPrevSuggestion();

        if (this.state.classes.indexOf('show') === -1) {
            this.setState({classes: ['founded']});

            setTimeout(()=>{
                this.setState({classes: ['founded', 'show']});
            }, 1100);
        }
    }

    getSuggestions(str) {
        if (str && str.length >= 3) {
            this.stopPrevSuggestion();
            this.lastQ = str;

            let currentNumber = this.counters.suggest;

            this.timers.suggest = setTimeout(()=>{
                $.ajax(this.props.suggestion_url, {
                    dataType: "json",
                    data: {'q': str},
                    success : (data)=>{
                        if (currentNumber === this.counters.suggest && data) {
                            this.setState({suggestions: data});
                        }
                        else {
                            this.setState({suggestions: this.getDefaultSuggestions()})
                        }
                    }
                });
            }, this.timeout);
        }
    }

    stopPrevSuggestion() {
        clearTimeout(this.timers.suggest);
        this.counters.suggest++;
    }

    renderSuggestion()
    {
        let result = [];

        if (this.state.suggestions && this.state.suggestions.suggests) {

            let selects  = this.state.suggestions.suggests.map((item)=>{
                return (<span className="suggest" onClick={()=>{ this.suggestionHandler(item);}}>
                        {item}
                </span>);
            });

            result.push(
                <div className="suggestions">
                    {selects}
                </div>
            );
        }

        return result;
    }

    renderPager() {
        if (this.state.p < this.state.p_max){
            return  <section className="front-endless-pager">
                        <a href="#"
                           onClick={this.paginateHandler.bind(this)}
                           className="show-more button yellow-white waves waves-orange"
                           data-text-loading="Loading ..."
                           data-text-default="Load more"
                           data-itemscope data-itemprop="relatedLink/pagination"
                           data-itemtype="http://schema.org/URL">
                                <span className="text">
                                    {this.state.loading ? 'Loading ...' : 'Load more'}
                                </span>
                            </a>
                    </section>;
        }
        return null;
    }

    renderProducts()
    {
        if (this.state.search_result && this.state.classes.indexOf('show') !== -1) {
            return (
            <div className="row w1280">
                <div className="columns large-12">
                    <ListProducts items={this.state.search_result} />
                    {this.renderPager()}
                </div>
            </div>);
        }

        return null;
    }

    render() {
        let blockClasses = ['search-block-wrapper'];

        blockClasses = blockClasses.concat(this.state.classes);
        blockClasses = blockClasses.join(' ');

        return (
            <div className={blockClasses}>
                <div className="search-block">
                    <form action={this.props.action} method="get" onSubmit={this.submitHandler.bind(this)}>
                        <div className="inputs">
                            <input type="search"
                                   name="q"
                                   autocomplete="off"
                                   value={this.state.q}
                                   placeholder={this.props.placeholder}
                                   onInput={linkState(this, 'q')}
                            />
                            <input type="submit" onClick={this.submitHandler.bind(this)}/>
                        </div>
                        {this.renderSuggestion()}
                    </form>
                </div>
                {this.renderProducts()}
            </div>
        );
    }
};