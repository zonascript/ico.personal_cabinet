<ul id="{$id}_errors" {if !$errors}style="display: none;"{/if} {raw $html}>
    {foreach $errors as $error}
        <li>{$error}</li>
    {/foreach}
</ul>