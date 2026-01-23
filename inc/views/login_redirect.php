<?php
/** @var string $target */
/** @var string $message */
?>
<input type="hidden" id="login_redirect_message" value="<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>" />
<input type="hidden" id="login_redirect_target" value="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>" />
<script type="text/javascript" src="../js/pages/login_redirect.min.js"></script>
