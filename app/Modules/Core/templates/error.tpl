{extends 'base.tpl'}

{block 'wrapper'}
    <div class="error-page">
        <div class="error-code">
            {$code}
        </div>
        <div class="message">
            {$exception->getMessage()}
        </div>
    </div>
{/block}