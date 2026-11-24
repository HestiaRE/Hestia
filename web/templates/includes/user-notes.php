<?php
// A failed read offers no form: saving an empty field over notes that only did not load would remove them.
exec(HESTIA_CMD . "h-list-user-notes " . $user . " json", $notes_out, $notes_rc);
$v_notes = $notes_rc === 0 ? (json_decode(implode("\n", $notes_out), true)["NOTES"] ?? null) : null;
unset($notes_out, $notes_rc);
?>
<div class="container">
	<?php if (!is_string($v_notes)) { ?>
		<p class="u-mt20 u-mb20 u-text-muted"><?= tohtml(_("The notes on this user could not be read.")) ?></p>
	<?php } else { ?>
	<form method="post" action="/edit/user/notes/" class="u-mt20 u-mb20">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="v_back" value="<?= tohtml(substr($page, 5)) ?>">
		<label for="v_user_notes" class="form-label">
			<?= tohtml(sprintf(_("Notes on %s"), trim($user, "'"))) ?>
			<span class="hint"><?= tohtml(_("only admins see them, they travel with the backup")) ?></span>
		</label>
		<?php if (!empty($_SESSION["user_notes_saved"])) {
			unset($_SESSION["user_notes_saved"]); ?>
			<p class="u-text-small u-mb10"><i class="fas fa-circle-check icon-green u-mr5"></i><?= tohtml(_("The notes have been saved.")) ?></p>
		<?php } ?>
		<textarea class="form-control u-min-height100 u-allow-resize" name="v_notes" id="v_user_notes"><?= tohtml($v_notes) ?></textarea>
		<button type="submit" class="button button-secondary u-mt10">
			<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml(_("Save notes")) ?>
		</button>
	</form>
	<?php } ?>
</div>
