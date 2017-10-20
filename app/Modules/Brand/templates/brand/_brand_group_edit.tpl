<fieldset class="expanded">
    <legend>
        Group brands
    </legend>
    <ul class="ul-main">
        <li>
            <div class="row">
                <div class="columns large-3">
                    <label for="o_parent_brand">Parent brand:</label>
                </div>
                <div class="columns large-7">
                    <select id="o_parent_brand" class="select2" name="BrandModel[parent_brand_id]" data-ajax-from="brand">
                        {if $model->parent_brand_id}
                            <option value="{raw $model->parent_brand_id}" selected>
                                {raw $model->parent->brand}
                            </option>
                        {/if}
                    </select>
                </div>
            </div>
        </li>
        {if !$model->parent_brand_id}
            <li>
                <div class="row">
                    <div class="columns large-3">
                        <label for="o_child_brands">Child brands:</label>
                    </div>
                    <div class="columns large-7">
                        <select id="o_child_brands" class="select2" name="child_brands[]" multiple data-ajax-from="brand">
                            {if !($model->getIsNewRecord())}
                                {foreach $model->child_brands as $value}
                                    <option value="{raw $value->brandid}" selected>
                                        {raw $value->brand}
                                    </option>
                                {/foreach}
                            {/if}
                        </select>
                    </div>
                </div>
            </li>
        {/if}
    </ul>
</fieldset>