{extends "admin/base.tpl"}

{block 'heading'}
    <h1>{t 'Creating'}</h1>
{/block}

{block 'main_block'}
    <div class="form-page {block 'page_class'}create{/block}">
        <form action="{$.request->getUrl()}" enctype="multipart/form-data" method="post">
            <div class="form-data">
                {include 'admin/form/_form.tpl'}
            </div>
            <div class="actions-panel">
                <div class="buttons">
                    <button type="submit" name="save" value="save" class="button pad round">
                        {t 'Save'}
                    </button>

                    <button type="submit" name="save" value="save-stay" class="button transparent pad round">
                        {t 'Save and continue'}
                    </button>

                    <button type="submit" name="save" value="save-create" class="button transparent pad round">
                        {t 'Save and create'}
                    </button>
                </div>

                <div class="links">
                    {if $model->pk && $.php.method_exists($model, 'getAbsoluteUrl')}
                        <a href="{$model->getAbsoluteUrl()}">
                            <i class="icon-watch_on_site"></i>
                            <span class="text">
                                {t 'Show on site'}
                            </span>
                        </a>
                    {/if}

                    {if $model->pk}
                        <a href="{$admin->getRemoveUrl($model->pk)}" data-all="{$admin->getAllUrl()}" data-prevention data-title="Вы действительно хотите удалить данный объект?" data-trigger="form-removed">
                            <i class="icon-delete_in_filter"></i>
                            <span class="text">
                                {t 'Delete'}
                            </span>
                        </a>
                    {/if}
                </div>
            </div>
        </form>
    </div>
{/block}
