<?php
$dns_section_labels = [
	"web" => _("Web"),
	"mail" => _("Mail"),
	"sub" => _("Subdomain"),
	"alias" => _("Alias"),
	"server" => _("Server"),
];
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

	<div class="alert alert-info u-mb20" role="alert">
		<i class="fas fa-exclamation"></i>
		<div>
			<p><?= tohtml(_("No provider account holds this zone. Enter the records at the provider by hand.")) ?></p>
			<?php if ($v_state === "unknown") { ?>
				<p><?= tohtml(_("Public DNS does not know this zone. Its sections are grouped under the shortest domain on this server.")) ?></p>
			<?php } ?>
		</div>
	</div>

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
				</div>
			<?php } ?>
		</div>
		<details class="u-mb20">
			<summary><?= tohtml(_("As zone file lines")) ?></summary>
			<textarea class="form-control u-console" readonly rows="<?= count($s["rows"]) + 1 ?>"><?= tohtml($s["text"]) ?></textarea>
		</details>
	<?php } ?>

	<?php if (count($sections) > 1) { ?>
		<h2 class="u-mt20 u-mb10"><?= tohtml(_("Whole zone")) ?></h2>
		<textarea class="form-control u-console u-mb20" readonly rows="10"><?= tohtml($v_zone_text) ?></textarea>
	<?php } ?>

</div>
