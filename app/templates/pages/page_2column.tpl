{extends  "pages/base.tpl"}
{block 'content'}
    <article class="pages page">
        <section class="heading">
            <div class="row">
                <div class="column large-12">
                    <h1>{$model->name}</h1>
                </div>
            </div>
        </section>
        <section class="page-container">
            <div class="row">
                <div class="column large-12 large-2column">
                    {raw $model->content}
                </div>
            </div>
        </section>
    </article>

{/block}