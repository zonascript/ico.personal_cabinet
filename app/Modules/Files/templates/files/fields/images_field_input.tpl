{extends 'files/fields/files_field_input.tpl'}

{block 'list'}
    <ul class="files-list images large-block-grid-6 clearfix">
        {foreach $items as $item}
            <li data-pk="{$item->id}">
                <div class="item-wrapper">
                    <span class="remove-link">
                        <i class="icon-delete_in_filter"></i>
                    </span>

                    <div class="image-wrapper">
                        <img src="{$item->getField($field->fileField)->url}">
                    </div>
                </div>
            </li>
        {/foreach}
    </ul>
{/block}