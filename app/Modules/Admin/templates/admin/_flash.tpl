<div class="flash-messages-block">
    <ul class="flash-list"></ul>
</div>

<script>
    window['flashStack'] = [];

    {foreach $messages as $item}
    window['flashStack'].push({ 'message': {$item['message']}, 'type': {$item['type']} });
    {/foreach}
</script>