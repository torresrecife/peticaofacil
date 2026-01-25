<?php
/** @var string $target */
/** @var string $message */
?>
<?php
	$baseUrl = getenv('APP_URL') ?: '';
	$basePath = $baseUrl ? rtrim(parse_url($baseUrl, PHP_URL_PATH), '/') : '';
?>
<base href="<?php echo $basePath ? $basePath.'/' : '/'; ?>">
<script type="text/javascript" src="js/jquery-1.8.0.min.js"></script>
<input type="hidden" id="login_redirect_message" value="<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>" />
<input type="hidden" id="login_redirect_target" value="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>" />
<script type="text/javascript" src="js/main.min.js"></script>
