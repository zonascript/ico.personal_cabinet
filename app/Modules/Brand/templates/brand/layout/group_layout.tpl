<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    {block 'js-head'}
    {/block}
    <link rel="stylesheet" href="/skin1_kolin/skin1_admin.css">
    <link rel="stylesheet" href="/static/backend/dist/css/main.css?v={backend_css_version}">
    <script type="text/javascript" src="/static/backend/dist/js/main.js?v={backend_js_version}"></script>


</head>
<body>
    <div id="main">
        {block 'main'}
            <div class="main-content">
                {block 'before-content'}

                {/block}

                <div class="brand-list">
                    <div class="">
                        {block 'content'}

                        {/block}
                    </div>
                </div>

                {block 'after-content'}

                {/block}
            </div>
        {/block}
    </div>
    {block 'js'}

    {/block}
</body>
</html>
