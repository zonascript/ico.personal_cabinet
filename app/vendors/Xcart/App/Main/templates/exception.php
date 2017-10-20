<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="ru"> <![endif]-->
<!--[if IE 7]> <html class="no-js lt-ie9 lt-ie8" lang="ru"> <![endif]-->
<!--[if IE 8]> <html class="no-js lt-ie9" lang="ru"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="ru"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <title><?php echo $data['type']; ?></title>
    <script type="text/javascript">
        <?php
        echo file_get_contents(__DIR__ . '/sh.js');
        ?>
    </script>
    <style type="text/css">
        <?php
        echo file_get_contents(__DIR__ . '/base.css');
        echo file_get_contents(__DIR__ . '/core.css');
        ?>
    </style>
</head>

<body>
<div class="base-container">
    <div class="message">
        <h1><?php echo $data['type']; ?></h1>
        <h2><?php echo $data['message']; ?></h2>
    </div>

    <div class="source">
        <p class="file"><?php echo $data['type']; ?>(<?php echo $data['line']; ?>)</p>
        <?php echo $this->renderSource($data['file'], $data['line'], $this->maxSourceLines); ?>
    </div>

    <div class="traces">
        <h2>Stack Trace</h2>
        <table style="width:100%;">
            <?php
            foreach ($data['traces'] as $i => $trace) {
                if ($this->isCore($trace)) {
                    $cssClass = 'core';
                } elseif ($i > 3) {
                    $cssClass = 'app';
                } else {
                    $cssClass = 'core';
                }

                $hasCode = $trace['file'] && is_file($trace['file']);
                ?>
                <tr class="trace <?php echo $cssClass ?>">
                    <td class="content">
                        <div class="trace-file">
                            #<?php echo $i ?> <?php echo $trace['file']; ?>(<?php echo $trace['line']; ?>):

                            <?php if (isset($trace['class'])) { ?>
                                <strong><?php echo $trace['class'] ?></strong> <?php echo $trace['type'] ?>
                            <?php } ?>

                            <?php if (isset($trace['args'])) { ?>
                                <strong><?php echo $trace['function'] ?></strong>
                                <?php echo $this->argsToString($trace['args']); ?>
                            <?php } ?>
                        </div>

                        <?php if ($hasCode) { ?>
                            <?php echo $this->renderSource($trace['file'], $trace['line'], $this->maxSourceLines); ?>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <div class="version">
        <?php echo date('Y-m-d H:i:s', $data['time']); ?> <?php echo $data['version']; ?>
    </div>
</div>
<script type="text/javascript">SyntaxHighlighter.all()</script>
<!--<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/default.min.css">-->
<!--<link href='//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/darcula.min.css' rel='stylesheet'/>-->
<!--<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>-->
<!--<script>hljs.initHighlightingOnLoad();</script>-->
</body>
</html>