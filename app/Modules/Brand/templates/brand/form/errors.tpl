<div class="errors-block">
    <div class="row">
        <div class="columns large-12">

            {if $errors}
                <div class="errors">
                    <span>
                        Please fix the following errors:
                    </span>
                    <ul class="fields">
                        {foreach $errors as $error}
                            <li>
                                {$error}
                            </li>
                        {/foreach}
                    </ul>
                </div>
            {/if}

        </div>
    </div>
</div>
