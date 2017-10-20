{if $messages}
    <div class="flash-messages-block">
        <ul class="flash-list">
            {foreach $messages as $item}
                <li class="{$item['type']}">
                    <div class="row">
                        <div class="column large-12">
                            <div class="message">
                                {$item['message']}
                            </div>
                        </div>
                    </div>
                </li>
            {/foreach}
        </ul>
    </div>
{/if}