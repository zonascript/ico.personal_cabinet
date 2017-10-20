<!DOCTYPE html>
<html>
<head>
    <meta charset=utf-8"/>
    <title>Internal Server Error</title>
    <style type="text/css">
        <?php echo file_get_contents(__DIR__ . '/base.css'); ?>
    </style>
</head>
<body>
<div class="base-container">
    <h1>Internal Server Error</h1>
    <h2><?php echo $data['message']; ?></h2>
    <p>An internal error occurred while the Web server was processing your request.</p>
    <p>Please contact <?php echo $data['admin'] ?> to report this problem.</p>
    <p>Thank you.</p>
    <hr>
    <div class="version">
        <?php echo date('Y-m-d H:i:s', $data['time']); ?><?php echo $data['version']; ?>
    </div>
</div>
</body>
</html>
