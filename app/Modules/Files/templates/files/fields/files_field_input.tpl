{set $instance = $field->getForm()->getInstance()}
{if $instance->id}
    <div class="files-input" id="{$id}">
        <section class="files-drop">
            <p class="info">Перетащите файлы сюда или нажмите для загрузки</p>
            <div class="progress">
                <div class="meter progress_bar"></div>
            </div>
        </section>

        {if $field->sortUrl && $field->sortField}
            {set $value = $value->order([$field->sortField])}
        {/if}
        {set $items = $value->all()}

        <section class="files-content">
            {block 'list'}
                <ul class="files-list">
                    {foreach $items as $item}
                        <li data-pk="{$item->id}">
                            <span class="name">
                                {$item}
                            </span>

                            <span class="remove-link">
                                <span class="remove">&times;</span>
                                <span class="text">
                                    Удалить
                                </span>
                            </span>
                        </li>
                    {/foreach}
                </ul>
            {/block}

            <p class="empty-info {if $items}hide{/if}">
                Здесь пока нет файлов
            </p>
        </section>

        <script type='text/javascript'>
            $('#{$id}').filesField({raw $field->getFieldData()});
        </script>
    </div>
{else}
    <div class="files-input unavailable">
        <div class="info">
            Пожалуйста, сохраните объект для работы с данным полем
        </div>
    </div>
{/if}
