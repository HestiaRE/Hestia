<?php
$firehol_age = function (int $ts): string {
	$s = max(0, time() - $ts);
	if ($s < 3600) {
		$n = intdiv($s, 60);
		return sprintf(ngettext("%d minute ago", "%d minutes ago", $n), $n);
	}
	if ($s < 172800) {
		$n = intdiv($s, 3600);
		return sprintf(ngettext("%d hour ago", "%d hours ago", $n), $n);
	}
	$n = intdiv($s, 86400);
	return sprintf(ngettext("%d day ago", "%d days ago", $n), $n);
};
$firehol_rows = function (array $lists) use ($v_selected, $firehol_age): void {
	foreach ($lists as $name => $l) {
		$id = "firehol-" . preg_replace("/[^A-Za-z0-9_-]/", "-", $name); ?>
		<div class="form-check u-mb10 js-firehol-row" data-name="<?= tohtml($name) ?>" data-entries="<?= tohtml($l["entries"]) ?>" data-includes="<?= tohtml(implode(" ", $l["includes"])) ?>">
			<input
				class="form-check-input js-firehol-list"
				type="checkbox"
				name="v_lists[]"
				value="<?= tohtml($name) ?>"
				id="<?= tohtml($id) ?>"
				<?= in_array($name, $v_selected, true) ? "checked" : "" ?>
			>
			<label for="<?= tohtml($id) ?>">
				<span class="u-text-bold"><?= tohtml($name) ?></span>
				<small class="hint">
					<?= tohtml($l["category"] ?? "") ?> &middot;
					<?= tohtml(sprintf(ngettext("%d entry", "%d entries", $l["entries"]), $l["entries"])) ?> &middot;
					<?= tohtml($firehol_age($l["updated"])) ?>
				</small>
				<?php if (!empty($l["includes"])) { ?>
					<br><small class="hint"><?= tohtml(sprintf(_("Contains %s"), implode(", ", $l["includes"]))) ?></small>
				<?php } elseif (!empty($l["info"])) { ?>
					<br><small class="hint"><?= tohtml($l["info"]) ?></small>
				<?php } ?>
			</label>
		</div>
	<?php }
};
?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/firewall/ipset/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
			<button type="submit" class="button button-secondary" form="firehol-refresh">
				<i class="fas fa-arrows-rotate icon-blue"></i><?= tohtml(empty($catalog["lists"]) ? _("Load FireHOL catalogue") : _("Refresh catalogue")) ?>
			</button>
		</div>
		<div class="toolbar-buttons">
			<?php if (!empty($catalog["lists"])) { ?>
				<button type="submit" class="button" form="main-form">
					<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml(_("Save")) ?>
				</button>
			<?php } ?>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<form id="firehol-refresh" method="post">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="action" value="refresh">
	</form>

	<form id="main-form" name="v_add_ipset_firehol" method="post">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="action" value="save">

		<div class="form-container">
			<h1 class="u-mb20"><?= tohtml(_("FireHOL Blocklists")) ?></h1>
			<?php show_alert_message($_SESSION); ?>

			<?php if (empty($catalog["lists"])) { ?>
				<p class="u-mb20"><?= tohtml(_("No FireHOL catalogue has been loaded on this server yet. Loading it asks iplists.firehol.org for its current lists.")) ?></p>
			<?php } else { ?>
				<p class="hint u-mb20">
					<?= tohtml(sprintf(_("Catalogue from %s."), date("Y-m-d H:i", $catalog["fetched"]))) ?>
					<?= tohtml(_("The ticked lists become one IP list with one DROP rule for TCP. Loopback and private networks are never dropped. Overlaps merge when it loads, so the kernel set holds fewer elements than the sum shown.")) ?>
				</p>

				<div class="u-mb20">
					<label for="v_setname" class="form-label"><?= tohtml(_("IP List Name")) ?></label>
					<input type="text" class="form-control" name="v_setname" id="v_setname" maxlength="64" value="<?= tohtml($v_setname) ?>" <?= $existing ? "readonly" : "" ?>>
				</div>

				<?php if (!empty($v_gone)) { ?>
					<div class="alert alert-info u-mb20" role="alert">
						<i class="fas fa-exclamation"></i>
						<p><?= tohtml(sprintf(_("No longer offered, dropped on the next save: %s"), implode(", ", $v_gone))) ?></p>
					</div>
				<?php } ?>

				<p class="u-mb20 u-text-bold js-firehol-sum" data-limit="200000"
					data-text="<?= tohtml(_("Selected: %d entries before merging")) ?>"
					data-warn="<?= tohtml(_("More than 200,000 entries cost seconds and memory on every refresh.")) ?>"></p>

				<h2 class="u-mb10"><?= tohtml(_("Combined lists")) ?></h2>
				<div class="u-mb20"><?php $firehol_rows($v_combined); ?></div>

				<h2 class="u-mb10"><?= tohtml(_("Largest lists")) ?></h2>
				<div class="u-mb20"><?php $firehol_rows($v_large); ?></div>

				<?php if (!empty($v_small)) { ?>
					<button type="button" class="button button-secondary u-mb20 js-firehol-show-small"><?= tohtml(sprintf(_("Show small lists (%d)"), count($v_small))) ?></button>
					<div class="u-hidden js-firehol-small">
						<h2 class="u-mb10"><?= tohtml(_("Small lists")) ?></h2>
						<?php $firehol_rows($v_small); ?>
					</div>
				<?php } ?>
			<?php } ?>
		</div>

	</form>

</div>
