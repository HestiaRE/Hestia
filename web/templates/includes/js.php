<?php /* Panel JS is served build-free: own code as native ES modules straight
from js/src (module scripts defer by default and execute in document order with
the deferred vendor scripts below - our alpine:init listeners register before
the Alpine core runs). Vendored libs come from upstream/* branches, see
VENDORED.json. */ ?>
<script type="module" src="/js/src/index.js?<?= JS_LATEST_UPDATE ?>"></script>
<script defer src="/js/vendor/alpinejs-collapse.min.js?<?= JS_LATEST_UPDATE ?>"></script>
<script defer src="/js/vendor/alpinejs.min.js?<?= JS_LATEST_UPDATE ?>"></script>
<script>
	document.documentElement.classList.replace('no-js', 'js');
	document.addEventListener('alpine:init', () => {
		// json_encode: an apostrophe in a translation or a newline in a message would end a quoted JS string.
		Alpine.store('globals', {
			USER_PREFIX: '<?= $user_plain ?>_',
			UNLIMITED: <?= json_encode(_("Unlimited"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>,
			NOTIFICATIONS_EMPTY: <?= json_encode(_("No notifications"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>,
			NOTIFICATIONS_DELETE_ALL: <?= json_encode(_("Delete all notifications"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>,
			CONFIRM_LEAVE_PAGE: <?= json_encode(_("Are you sure you want to leave the page?"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>,
			ERROR_MESSAGE: <?= json_encode(!empty($_SESSION["error_msg"]) ? htmlentities($_SESSION["error_msg"], ENT_QUOTES) : "", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>,
			BLACKLIST: <?= json_encode(_("BLACKLIST"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>,
			IPVERSE: <?= json_encode(_("IPVERSE"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
		});
	})
</script>
<?php $_SESSION["unset_alerts"] = true; ?>

<?php
$customScriptDirectory = new DirectoryIterator($_SERVER["HESTIA"] . "/web/js/custom_scripts");
foreach ($customScriptDirectory as $customScript) {
	$extension = $customScript->getExtension();
	if ($extension === "js") {
		$customScriptPath = "/js/custom_scripts/" . rawurlencode($customScript->getBasename());
		echo '<script defer src="' . $customScriptPath . '"></script>';
	} elseif ($extension === "php") {
		require_once $customScript->getPathname();
	}
} ?>
