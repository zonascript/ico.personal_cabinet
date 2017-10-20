<div class="row w1280">
    <div class="columns small-12">
        <div class="flash-messages-block">
            <div class="flash-list"></div>
        </div>
    </div>
</div>

<script>
    window['flashStack'] = [];

    {foreach $messages as $item}
    window['flashStack'].push({ 'message': {$item['message']}, 'type': {$item['type']} });
    {/foreach}
</script>