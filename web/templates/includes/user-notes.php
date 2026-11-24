<?php
// A failed read offers no form: saving an empty field over notes that only did not load would remove them.
exec(HESTIA_CMD . "h-list-user-notes " . $user . " json", $notes_out, $notes_rc);
$notes = $notes_rc === 0 ? json_decode(implode("\n", $notes_out), true) : null;
$v_notes = is_array($notes) && is_string($notes["NOTES"] ?? null) ? $notes["NOTES"] : null;
$v_notes_from = is_array($notes) ? (string) ($notes["RESTORED_FROM"] ?? "") : "";
$v_notes_date = is_array($notes) ? (string) ($notes["RESTORED_DATE"] ?? "") : "";
// Read only on the protected admin account only: $read_only also covers a suspended customer, whose notes are still
// the admin's to edit.
$v_notes_ro = is_root_user($_SESSION["look"] ?? "") && ($_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] ?? "") === "yes";
unset($notes_out, $notes_rc, $notes);
?>
<div class="container user-notes">
	<?php if ($v_notes === null) { ?>
		<p class="u-mt20 u-mb20 u-text-muted"><?= tohtml(_("The notes on this user could not be read.")) ?></p>
	<?php } else { ?>
	<form method="post" action="/edit/user/notes/" class="u-mt20 u-mb20">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="v_back" value="<?= tohtml(substr($page, 5)) ?>">
		<input type="hidden" name="v_user" value="<?= tohtml($user_plain) ?>">
		<label for="v_user_notes" class="form-label">
			<?= tohtml(sprintf(_("Notes on %s"), trim($user, "'"))) ?>
			<span class="hint"><?= tohtml(_("not shown in the customer's panel, but part of their backup: no passwords or keys")) ?></span>
		</label>
		<?php if (!empty($_SESSION["user_notes_saved"])) {
			unset($_SESSION["user_notes_saved"]); ?>
			<p class="u-text-small u-mb10"><i class="fas fa-circle-check icon-green u-mr5"></i><?= tohtml(_("The notes have been saved.")) ?></p>
		<?php } ?>
		<?php if ($v_notes_from !== "") { ?>
			<p class="u-text-small u-mb10"><i class="fas fa-triangle-exclamation icon-orange u-mr5"></i><?= tohtml(sprintf(_("Restored from %s on %s: the customer could have changed them in the archive. Saving them marks them as checked."), $v_notes_from, $v_notes_date)) ?></p>
		<?php } ?>
		<?php // A newline right after the tag: the browser drops the first one, a leading blank line of the notes would go.?>
		<textarea class="form-control u-min-height100 u-allow-resize" name="v_notes" id="v_user_notes"<?= $v_notes_ro ? " readonly" : "" ?>>
<?= tohtml($v_notes) ?></textarea>
		<?php if (!$v_notes_ro) { ?>
			<button type="submit" class="button button-secondary u-mt10">
				<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml(_("Save notes")) ?>
			</button>
		<?php } ?>
	</form>
	<?php } ?>
</div>
