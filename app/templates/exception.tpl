<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="ru"> <![endif]-->
<!--[if IE 7]> <html class="no-js lt-ie9 lt-ie8" lang="ru"> <![endif]-->
<!--[if IE 8]> <html class="no-js lt-ie9" lang="ru"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="ru"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <title>{$data.type}</title>
</head>

<body>
<div class="container">
    <div class="message">
        <h1>{$data.type|escape}</h1>
        <h2>{$data.message|escape}</h2>
    </div>

    <div class="source">
        <p class="file">{$data.file}({$data.line})</p>
        {raw $this->renderSource($data.file, $data.line, $this->maxSourceLines)}
    </div>

    <div class="traces">
        <h2>Stack Trace</h2>
        <table style="width:100%;">
            {foreach $data.traces as $trace index=$index}
                {if $this->isCore($trace)}
                    {set $cssClass = 'core'}
                {elseif $index > 3}
                    {set $cssClass = 'app'}
                {else }

                    {set $cssClass = 'core'}
                {/if}

            {set $hasCode = $trace.file and is_file($trace.file) }

            <tr class="trace {$cssClass}">
                  <td class="content">
                        <div class="trace-file">
                            #{$index} {$trace.file}({$trace.line}):

                            {if $trace.class! && $trace.class}
                                <strong>{$trace.class}</strong> {$trace.type}
                            {/if}

                            {if $trace.args}
                                <strong>{$trace.function}</strong>
                                {if $trace.args|length > 0 }
                                    {$this->argsToString($trace.args)}
                                {/if}
                            {/if}
                        </div>

                      {if $hasCode}
                        {raw $this->renderSource($trace.file, $trace.line, $this->maxSourceLines)}
                      {/if}
                    </td>
                </tr>
            {/foreach}
        </table>
    </div>

    <div class="version">
        {date('Y-m-d H:   i:s', $data.time)} {raw $data.version}
    </div>
</div>
<style type="text/css">{include "core/exception.css"}{include "core/core.css"}</style>
<script src="//cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shCore.min.js" type="text/javascript"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shBrushJScript.min.js" type="text/javascript"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shBrushPhp.min.js" type="text/javascript"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shBrushCss.min.js" type="text/javascript"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shBrushXml.js" type="text/javascript"></script>
<script type="text/javascript">
    SyntaxHighlighter.all()
</script>
</body>
</html>