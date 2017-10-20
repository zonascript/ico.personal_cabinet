import { h, render } from 'preact';
import  Search from "../components/Search";

(_=>{
    let $page = $('.page.shop');
    if ($page.length) {
        let $block = $page.find('.search-block-wrapper');
        let $form = $block.find('form');
        let $input = $block.find('input[name="q"]');
        let parentNode = $block.parent()[0];

        let placeholder = $input.attr('placeholder');
        let url_suggestion = $input.data('suggestion-url');
        let url_search = $input.data('search-url');
        let products_pp = $input.data('products-pp');
        let action = $form.attr('action');
        let val = $input.val();


        $block.remove();

        render(<Search q={val} action={action} placeholder={placeholder} suggestion_url={url_suggestion} search_url={url_search} products_pp={products_pp} />, parentNode);
    }
})();
