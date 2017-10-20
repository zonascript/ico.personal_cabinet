{extends 'base.tpl'}

{block 'content'}
    <section class="page pages error-page">
        <div class="row w1280">
            <div class="columns small-12">
                <section class="error-data">
                    <section class="error-code">
                        {block 'error_code'}
                            {$data.code}
                        {/block}
                    </section>
                    <section class="error-info">
                        <section class="multiline">
                            <section class="error-title">
                                {block 'error_title'}
                                    {if $data.code == 404}
                                        Page not found
                                    {else}
                                        Internal server error
                                    {/if}
                                {/block}
                            </section>
                            <section class="error-description">
                                {block 'error_description'}
                                    <a href="/">To home &rarr;</a>
                                {/block}
                            </section>
                        </section>
                    </section>
                </section>
            </div>
        </div>


    </section>
{/block}