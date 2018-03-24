<?php
const __PROJECT_ROOT__ = __DIR__ . "/../";
$config = require_once __PROJECT_ROOT__ . "config/config.php";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<pre><code class="subscription-info"></code></pre>
<script src="scripts/push-notification-manager.js"></script>
<script>
    PushNotificationManager('<?= $config['vapidPublicKey']?>')
        .then(manager => manager.subscribe())
        .catch(err => console.error(err));
</script>
</body>
</html>
