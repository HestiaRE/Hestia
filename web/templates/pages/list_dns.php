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
			<a href="/list/dns/?providers=1" class="button button-secondary">
				<i class="fas fa-key icon-blue"></i><?= tohtml(_("DNS Providers")) ?>
			</a>
		</div>
		<div class="toolbar-right"></div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30"><?= tohtml(_("DNS Zones")) ?></h1>

	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(_("Zone")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Sections")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Users")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Records")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Provider")) ?></div>
		</div>

		<?php foreach ($data as $zone => $z) {
			$labels = array_map(fn ($k) => $dns_section_labels[$k] ?? $k, array_filter(explode(",", $z["SECTIONS"] ?? "")));
			?>
			<div class="units-table-row js-unit">
				<div class="units-table-cell units-table-heading-cell u-text-bold">
					<span class="u-hide-desktop"><?= tohtml(_("Zone")) ?>:</span>
					<a href="/list/dns/?<?= tohtml(http_build_query(["zone" => $zone])) ?>"><?= tohtml($zone) ?></a>
					<?php if (($z["STATE"] ?? "") === "unknown") { ?>
						<i class="fas fa-circle-question icon-dim u-ml5" title="<?= tohtml(_("Public DNS does not know this zone. Its sections are grouped under the shortest domain on this server.")) ?>"></i>
					<?php } ?>
				</div>
				<div class="units-table-cell">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Sections")) ?>:</span>
					<?= tohtml(implode(", ", $labels)) ?>
					<?php if (($z["APEX"] ?? "") === "no") { ?>
						<span class="u-text-muted">(<?= tohtml(_("only subdomains")) ?>)</span>
					<?php } ?>
				</div>
				<div class="units-table-cell">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Users")) ?>:</span>
					<?= tohtml(str_replace(",", ", ", $z["USERS"] ?? "")) ?>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Records")) ?>:</span>
					<?= tohtml($z["ROWS"] ?? "") ?>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Provider")) ?>:</span>
					<span class="u-text-muted"><?= tohtml(_("no account")) ?></span>
				</div>
			</div>
		<?php } ?>
	</div>

	<?php if (empty($data)) { ?>
		<p class="u-text-center u-mt20"><?= tohtml(_("No web or mail domain of this user lies in a DNS zone.")) ?></p>
	<?php } ?>

</div>
