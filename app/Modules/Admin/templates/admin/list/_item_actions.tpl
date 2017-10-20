{var $actions = $admin->getListItemActions()}

{if "update" in $actions}
    <a href="{$admin->getUpdateUrl($pk)}">
        <i class="icon-edit"></i>
    </a>
{/if}

{if ("view" in $actions) && $.php.method_exists($item, 'getAbsoluteUrl')}
    <a href="{$item->getAbsoluteUrl()}">
        <i class="icon-search_mark"></i>
    </a>
{/if}

{if "info" in $actions}
    <a href="{$admin->getInfoUrl($pk)}">
        <i class="icon-info"></i>
    </a>
{/if}

{if "remove" in $actions}
    <a href="{$admin->getRemoveUrl($pk)}" data-prevention data-title="Вы действительно хотите удалить данный объект?" data-trigger="list-update">
        <i class="icon-delete_in_table"></i>
    </a>
{/if}