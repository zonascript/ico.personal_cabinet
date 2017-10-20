{extends 'base/admin.tpl'}

{block 'heading'}
    <h1 align="center">
        {if $model->getIsNewRecord()}
            Add brand
        {else}
            Brand ({$model->brand})
        {/if}
    </h1>
{/block}

{block 'content'}
    {smarty_admin_block name='Brand details'}
        <div class="row">
            <div class="columns large-12">
                {include 'brand/form/errors.tpl' model=$model}
            </div>
        </div>
        <form action="{$model->getAdminUrl()}" method="POST" class="brand-form">
            <fieldset class="expanded">
                <legend>
                    Brand info
                </legend>
                <ul class="ul-main">
                    <li>
                        <div class="row">
                            <div class="columns large-10"></div>
                            <div class="columns large-2 padding-right-0">
                                <a href="{url 'brand:brand_list'}">Brand list</a>
                                <a href="{url 'brand:create_brand'}">Add brand</a>
                            </div>

                        </div>
                    </li>
                    {if !$model->getIsNewRecord()}
                        <li>
                            <div class="row">
                                <div class="columns large-12">
                                    <span class="brand-detail-title">
                                        <a href="{$model->getAdminUrl()}" title="{$model->brand}" target="_blank">Current brand: "{$model->brand}"</a>
                                    </span>
                                </div>
                            </div>
                        </li>
                    {/if}
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='brand' class='s-big' lcw=3 rcw=7}
                    </li>
                    <li>
                        <div class="row">
                            <div class="columns large-3">
                                <label>Clean URL:</label>
                            </div>
                            <div class="columns large-7">
                                <span>{$model->getUrl()}</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="row">
                            <div class="columns large-3">
                                <label>Logo:</label>
                            </div>
                            <div class="columns large-7">
                                <img src="/image.php?type=B&id={$model->brandid}"/>
                            </div>
                        </div>
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='descr' class='s-big' lcw=3 rcw=7 type="textarea"}
                    </li>
                    <li>
                        <div class="row">
                            <div class="columns large-3">
                                <label for="o_url">URL (include http://):</label>
                            </div>
                            <div class="columns large-7">
                                <input id="o_url" type="text" class="s-big" name="BrandModel[url]" value="{$model->url}"/>
                            </div>
                            {if $model->url}
                                <div class="columns large-2 padding-right-0">
                                    <a href="{$model->url}" target="_blank">Webpage</a>
                                </div>
                            {/if}
                        </div>
                    </li>
                    <li>
                        <div class="row">
                            <div class="columns large-3">
                                <label for="o_link_to_us_url">Link to us URL (include http://):</label>
                            </div>
                            <div class="columns large-7">
                                <input id="o_link_to_us_url" type="text" class="s-big" name="BrandModel[link_to_us_url]"
                                       value="{$model->link_to_us_url}"/>
                            </div>
                            {if $model->link_to_us_url}
                                <div class="columns large-2 padding-right-0">
                                    <a href="{$model->link_to_us_url}">Link to us webpage</a>
                                </div>
                            {/if}
                        </div>
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='customer_service_name' class='s-big' lcw=3 rcw=7}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='customer_service_phone' class='s-big' lcw=3 rcw=7}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='customer_service_email' class='s-big' lcw=3 rcw=7}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='disclaimer_text' class='s-big' lcw=3 rcw=7 type="textarea"}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='orderby' class='s-big' lcw=3 rcw=7}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='avail' class='s-big' lcw=3 rcw=7 type="checkbox"}
                    </li>
                </ul>
            </fieldset>
            <fieldset class="expanded">
                <legend>
                    SEO options
                </legend>
                <ul class="ul-main">
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='prevent_search_indexing_of_all_brand_products' class='s-big' lcw=3 rcw=7 type="checkbox"}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='prevent_search_indexing_brand_page' class='s-big' lcw=3 rcw=7 type="checkbox"}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='title' class='s-big' lcw=3 rcw=7}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='SEO_brand_name_h1' class='s-big' lcw=3 rcw=7}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='SEO_h2' class='s-big' lcw=3 rcw=7}
                    </li>
                    <li>
                        {include 'core/form/model_form_field.tpl' model=$model field='meta_descr' class='s-big' lcw=3 rcw=7 type="textarea"}
                    </li>
                </ul>

            </fieldset>

            {include 'brand/_brand_group_edit.tpl'}

            {include 'brand/_exluded_marketplaces.tpl'}

            {include 'core/form/buttons.tpl'}
        </form>
    {/smarty_admin_block}

{/block}

{block 'js'}
    {parent}
    <script type="text/javascript">
        $('select.select2:not([data-ajax-from]').select2({
            allowClear: true,
            closeOnSelect: false,
            placeholder: 'Select options'
        });

        $('.admin select[data-ajax-from]').select2({
            allowClear: true,
            placeholder: 'Start typing for hint',
            tags: true,
            closeOnSelect: false,
            minimumInputLength: 3,
            createTag : function (params) {
                if (!this.$element.data('combobox')) {
                    return null;
                }

                var term = $.trim(params.term);

                if (term === '') {
                    return null;
                }

                return {
                    id: '{$manual_string}' + term,
                    text: '{raw $manual_string}' + term
                }
            },
            ajax: {
                cache: true,
                dataType: 'json',
                delay: 500,
                url : function(params)
                {
                    var url = '{url 'dashboard:search_suggestion'}';
                    var combobox = 0;
                    var delimiter = '?';
                    if ($(this).data('combobox')) {
                        combobox = 1;
                    }

                    if (url.indexOf(delimiter, 0) !== -1) {
                        delimiter = '&';
                    }

                    return url + delimiter + 'from=' + $(this).data('ajax-from') + '&combobox=' + combobox;
                },
                processResults: function (data) {
                    if (data) {
                        return {
                            results: data
                        };
                    }
                    return { results: { } };
                }
            }
        });
    </script>
    ;
{/block}