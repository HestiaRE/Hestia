<?php
$dns_section_labels = [
	"web" => _("Web"),
	"mail" => _("Mail"),
	"sub" => _("Subdomain"),
	"alias" => _("Alias"),
	"server" => _("Server"),
	"orphan" => _("Orphaned"),
];
$dns_op_labels = [
	"add" => ["fa-circle-plus icon-green", _("add")],
	"rem" => ["fa-circle-minus icon-red", _("remove")],
	"main" => ["fa-pen icon-blue", _("set main IP")],
	"include" => ["fa-link-slash icon-orange", _("www include off")],
	"skip" => ["fa-circle-pause icon-dim", _("left alone")],
];
?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="<?= tohtml($back) ?>">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
		<div class="toolbar-right"></div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-mt20 u-mb10"><?= tohtml($v_zone) ?></h1>
	<h2 class="u-mb20"><?= tohtml($dns_section_labels[$v_section] ?? $v_section) ?>: <?= tohtml($v_object) ?></h2>
	<?php show_alert_message($_SESSION); ?>

	<?php if ($v_writes === 0) { ?>
		<div class="alert alert-success u-mb20" role="alert">
			<i class="fas fa-check"></i>
			<p><?= tohtml(_("Nothing to write: the provider holds what HestiaRE derives, or every difference is left alone below.")) ?></p>
		</div>
	<?php } else { ?>
		<div class="alert alert-info u-mb20" role="alert">
			<i class="fas fa-exclamation"></i>
			<?php if ($v_section === "orphan") { ?>
				<p><?= tohtml(sprintf(ngettext("%d record HestiaRE wrote for %s is removed from the provider. No other record is touched.", "%d records HestiaRE wrote for %s are removed from the provider. No other record is touched.", $v_writes), $v_writes, $v_object)) ?></p>
			<?php } else { ?>
				<p><?= tohtml(sprintf(ngettext("%d change goes to the provider. Records no row of this section derives are never touched.", "%d changes go to the provider. Records no row of this section derives are never touched.", $v_writes), $v_writes)) ?></p>
			<?php } ?>
		</div>
	<?php } ?>

	<?php if ($ops) { ?>
		<div class="units-table js-units-container u-mb20">
			<div class="units-table-header">
				<div class="units-table-cell"><?= tohtml(_("Action")) ?></div>
				<div class="units-table-cell"><?= tohtml(_("Record")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(_("Type")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(_("Priority")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(_("TTL")) ?></div>
				<div class="units-table-cell"><?= tohtml(_("IP or Value")) ?></div>
			</div>
			<?php foreach ($ops as $o) {
				[$icon, $label] = $dns_op_labels[$o["OP"] ?? ""] ?? ["fa-circle-question icon-dim", (string) ($o["OP"] ?? "")];
				?>
				<div class="units-table-row js-unit">
					<div class="units-table-cell u-text-bold">
						<i class="fas <?= tohtml($icon) ?> u-mr5"></i><?= tohtml($label) ?>
					</div>
					<div class="units-table-cell u-text-break">
						<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Record")) ?>:</span>
						<?= tohtml($o["NAME"] ?? "") ?>
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop"><?= tohtml($o["TYPE"] ?? "") ?></div>
					<div class="units-table-cell u-text-center-desktop"><?= tohtml($o["PRIORITY"] ?? "") ?></div>
					<div class="units-table-cell u-text-center-desktop"><?= tohtml($o["TTL"] ?? "") ?></div>
					<div class="units-table-cell u-text-break">
						<?= tohtml($o["VALUE"] ?? "") ?>
						<?php if (($o["NOTE"] ?? "") !== "") { ?>
							<br><span class="u-text-muted u-text-small"><?= tohtml($o["NOTE"]) ?></span>
						<?php } ?>
					</div>
				</div>
			<?php } ?>
		</div>
	<?php } ?>

	<?php
	$dns_confirm_labels = [
		"cname" => [_("Replace the CNAME by records of its own"), _("Keep the CNAME")],
		"include" => [_("Switch the www include off, www gets records of its own"), _("Keep the www include")],
	];
foreach (array_unique(array_merge($v_confirmable, $v_confirm)) as $c) {
	if (!isset($dns_confirm_labels[$c])) {
		continue;
	}
	$on = in_array($c, $v_confirm, true);
	$next = $on ? array_values(array_diff($v_confirm, [$c])) : array_merge($v_confirm, [$c]);
	?>
		<p class="u-mb10">
			<a class="button button-secondary" href="<?= tohtml($v_confirm_query($next)) ?>">
				<i class="fas <?= $on ? "fa-rotate-left icon-blue" : "fa-check icon-orange" ?>"></i><?= tohtml($dns_confirm_labels[$c][$on ? 1 : 0]) ?>
			</a>
		</p>
	<?php } ?>

	<?php if ($v_writes > 0) { ?>
		<form method="post" action="<?= tohtml($v_confirm_query($v_confirm)) ?>">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="v_plan" value="<?= tohtml($v_plan) ?>">
			<p class="hint u-mb10"><?= tohtml(_("The zone is read again before the write: if it changed since this preview, nothing is written. Afterwards it is read once more and compared.")) ?></p>
			<button type="submit" class="button">
				<i class="fas fa-upload icon-green"></i><?= tohtml(_("Write to provider")) ?>
			</button>
		</form>
	<?php } ?>

</div>
