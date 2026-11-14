<?php
$dns_section_labels = [
	"web" => _("Web"),
	"mail" => _("Mail"),
	"sub" => _("Subdomain"),
	"alias" => _("Alias"),
	"server" => _("Server"),
];
$dns_status_labels = [
	"ok" => ["fa-circle-check icon-green", _("ok")],
	"missing" => ["fa-circle-plus icon-orange", _("missing")],
	"different" => ["fa-triangle-exclamation icon-orange", _("different")],
	"cname" => ["fa-triangle-exclamation icon-orange", _("CNAME")],
	"wildcard" => ["fa-triangle-exclamation icon-orange", _("wildcard")],
	"delegated" => ["fa-circle-pause icon-dim", _("delegated")],
	"unchecked" => ["fa-circle-question icon-dim", _("unchecked")],
];
// The records of the provider in one table: name, type, priority, TTL, value and what the driver said about them.
$dns_provider_rows = function (array $rows): void {
	?>
	<div class="units-table js-units-container u-mb10">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(_("Record")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Type")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Priority")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("TTL")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("IP or Value")) ?></div>
		</div>
		<?php foreach ($rows as $r) { ?>
			<div class="units-table-row js-unit">
				<div class="units-table-cell u-text-break"><?= tohtml($r["NAME"]) ?></div>
				<div class="units-table-cell u-text-bold u-text-center-desktop"><?= tohtml($r["TYPE"]) ?></div>
				<div class="units-table-cell u-text-center-desktop"><?= tohtml($r["PRIORITY"]) ?></div>
				<div class="units-table-cell u-text-center-desktop"><?= tohtml($r["TTL"]) ?></div>
				<div class="units-table-cell u-text-break">
					<?= tohtml($r["VALUE"]) ?>
					<?php if (($r["NOTE"] ?? "") !== "") { ?>
						<br><span class="u-text-muted u-text-small"><?= tohtml($r["NOTE"]) ?></span>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
	</div>
	<?php
};
?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/dns/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
		<div class="toolbar-right"></div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-mt20 u-mb20"><?= tohtml($v_zone) ?></h1>

	<?php if ($v_account === "") { ?>
		<div class="alert alert-info u-mb20" role="alert">
			<i class="fas fa-exclamation"></i>
			<div>
				<p><?= tohtml(_("No provider account holds this zone. Enter the records at the provider by hand.")) ?></p>
				<?php if ($v_state === "unknown") { ?>
					<p><?= tohtml(_("Public DNS does not know this zone. Its sections are grouped under the shortest domain on this server.")) ?></p>
				<?php } ?>
			</div>
		</div>
	<?php } elseif (!$v_checked) { ?>
		<div class="alert alert-danger u-mb20" role="alert">
			<i class="fas fa-exclamation"></i>
			<div>
				<p><?= tohtml(sprintf(_("The zone could not be read from account %s. Only the records HestiaRE derives are shown."), $v_account)) ?></p>
				<?php if ($v_check_error !== "") { ?>
					<p><?= tohtml($v_check_error) ?></p>
				<?php } ?>
			</div>
		</div>
	<?php } elseif ($v_differences === 0) { ?>
		<div class="alert alert-success u-mb20" role="alert">
			<i class="fas fa-check"></i>
			<p><?= tohtml(sprintf(_("Compared with account %s just now: every record HestiaRE derives is in place."), $v_account)) ?></p>
		</div>
	<?php } else { ?>
		<div class="alert alert-info u-mb20" role="alert">
			<i class="fas fa-exclamation"></i>
			<p><?= tohtml(sprintf(ngettext("Compared with account %2\$s just now: %1\$d record differs from what HestiaRE derives. Nothing is changed at the provider.", "Compared with account %2\$s just now: %1\$d records differ from what HestiaRE derives. Nothing is changed at the provider.", $v_differences), $v_differences, $v_account)) ?></p>
		</div>
	<?php } ?>
	<?php if ($v_delegation !== null && $v_delegation["STATUS"] !== "ok") { ?>
		<div class="alert alert-danger u-mb20" role="alert">
			<i class="fas fa-triangle-exclamation"></i>
			<div>
				<?php if ($v_delegation["STATUS"] === "unchecked") { ?>
					<p><?= tohtml(_("Public DNS did not answer for the name servers of this zone: whether anyone reads it at the provider is not known.")) ?></p>
				<?php } else { ?>
					<p><?= tohtml(_("Nobody reads this zone at the provider: the public name servers of the domain are not those of the account. After a move the zone is often left behind there.")) ?></p>
				<?php } ?>
				<p class="u-text-small">
					<?= tohtml(_("Provider")) ?>: <?= tohtml($v_delegation["VALUE"] !== "" ? $v_delegation["VALUE"] : "-") ?><br>
					<?= tohtml(_("Public DNS")) ?>: <?= tohtml($v_delegation["ACTUAL"] !== "" ? $v_delegation["ACTUAL"] : "-") ?>
				</p>
			</div>
		</div>
	<?php } ?>

	<?php
	$anchored = [];
foreach ($sections as $s) {
	// The jump from a domain lands at its first section, the one from the mail list at its first mail section.
	$key = ($s["kind"] === "mail" ? "obj-mail-" : "obj-") . $s["object"];
	$id = isset($anchored[$key]) ? "" : $key;
	$anchored[$key] = true;
	?>
		<h2 class="u-mt20 u-mb10"<?= $id !== "" ? ' id="' . tohtml($id) . '"' : "" ?>>
			<?= tohtml($dns_section_labels[$s["kind"]] ?? $s["kind"]) ?>: <?= tohtml($s["object"]) ?>
			<span class="u-text-muted u-text-small">
				<?php if ($s["owner"] !== $user_plain) { ?>
					<i class="fas fa-user icon-dim u-ml5"></i> <?= tohtml(sprintf(_("user %s"), $s["owner"])) ?>
				<?php } ?>
			</span>
		</h2>
		<?php if ($s["kind"] === "alias") { ?>
			<p class="u-mb10">
				<?= tohtml(_("Alias of")) ?>
				<?php if ($s["home"] !== "") { ?>
					<a href="/list/dns/?<?= tohtml(http_build_query(["zone" => $s["home"]])) ?>#<?= tohtml(rawurlencode("obj-" . $s["object"])) ?>"><?= tohtml($s["object"]) ?></a>
				<?php } else { ?>
					<?= tohtml($s["object"]) ?>
				<?php } ?>
			</p>
		<?php } ?>
		<?php if (!empty($s["aliases"])) { ?>
			<p class="u-mb10">
				<?= tohtml(_("Aliases in other zones")) ?>:
				<?= implode(", ", array_map(fn ($z) => '<a href="/list/dns/?' . tohtml(http_build_query(["zone" => $z])) . '">' . tohtml($z) . "</a>", $s["aliases"])) ?>
			</p>
		<?php } ?>

		<div class="units-table js-units-container u-mb10">
			<div class="units-table-header">
				<div class="units-table-cell"><?= tohtml(_("Record")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(_("Type")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(_("Priority")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(_("TTL")) ?></div>
				<div class="units-table-cell"><?= tohtml(_("IP or Value")) ?></div>
				<?php if ($v_checked) { ?>
					<div class="units-table-cell"><?= tohtml(_("At the provider")) ?></div>
				<?php } ?>
			</div>
			<?php foreach ($s["rows"] as $r) { ?>
				<div class="units-table-row js-unit">
					<div class="units-table-cell">
						<label class="u-hide-desktop u-text-bold"><?= tohtml(_("Record")) ?>:</label>
						<input type="text" class="form-control" readonly value="<?= tohtml($r["NAME"]) ?>">
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop">
						<span class="u-hide-desktop"><?= tohtml(_("Type")) ?>:</span>
						<?= tohtml($r["TYPE"]) ?>
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop">
						<span class="u-hide-desktop"><?= tohtml(_("Priority")) ?>:</span>
						<?= tohtml($r["PRIORITY"]) ?>
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop">
						<span class="u-hide-desktop"><?= tohtml(_("TTL")) ?>:</span>
						<?= tohtml($r["TTL"]) ?>
					</div>
					<div class="units-table-cell">
						<label class="u-hide-desktop u-text-bold"><?= tohtml(_("IP or Value")) ?>:</label>
						<input type="text" class="form-control" readonly value="<?= tohtml($r["VALUE"]) ?>">
					</div>
					<?php if ($v_checked) {
						[$icon, $label] = $dns_status_labels[$r["STATUS"]] ?? ["fa-circle-question icon-dim", $r["STATUS"]];
						?>
						<div class="units-table-cell u-text-break">
							<span class="u-hide-desktop u-text-bold"><?= tohtml(_("At the provider")) ?>:</span>
							<i class="fas <?= tohtml($icon) ?> u-mr5" title="<?= tohtml($label) ?>"></i><span class="u-text-bold"><?= tohtml($label) ?></span>
							<?php if ($r["ACTUAL"] !== "" && $r["STATUS"] !== "ok") { ?>
								<br><?= tohtml($r["ACTUAL"]) ?>
							<?php } ?>
							<?php if (($r["NOTE"] ?? "") !== "") { ?>
								<br><span class="u-text-muted u-text-small"><?= tohtml($r["NOTE"]) ?></span>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
		<details class="u-mb20">
			<summary><?= tohtml(_("As zone file lines")) ?></summary>
			<textarea class="form-control u-console" readonly rows="<?= count($s["rows"]) + 1 ?>"><?= tohtml($s["text"]) ?></textarea>
		</details>
	<?php } ?>

	<?php if ($v_checked) { ?>
		<h2 class="u-mt20 u-mb10"><?= tohtml(_("Other records at the provider")) ?></h2>
		<?php if ($foreign) { ?>
			<details class="u-mb20">
				<summary><?= tohtml(sprintf(ngettext("%d record no object on this server derives. HestiaRE never changes it.", "%d records no object on this server derives. HestiaRE never changes them.", count($foreign)), count($foreign))) ?></summary>
				<?php $dns_provider_rows($foreign); ?>
			</details>
		<?php } else { ?>
			<p class="u-mb20"><?= tohtml(_("None.")) ?></p>
		<?php } ?>
		<?php if ($hidden) { ?>
			<details class="u-mb20">
				<summary><?= tohtml(sprintf(ngettext("%d record elsewhere in the zone, outside the names of this server.", "%d records elsewhere in the zone, outside the names of this server.", count($hidden)), count($hidden))) ?></summary>
				<?php $dns_provider_rows($hidden); ?>
			</details>
		<?php } ?>
	<?php } ?>

	<?php if (count($sections) > 1) { ?>
		<h2 class="u-mt20 u-mb10"><?= tohtml(_("Whole zone")) ?></h2>
		<textarea class="form-control u-console u-mb20" readonly rows="10"><?= tohtml($v_zone_text) ?></textarea>
	<?php } ?>

</div>
