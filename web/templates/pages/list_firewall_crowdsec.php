<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/firewall/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30"><?= tohtml(_("CrowdSec Scenarios")) ?></h1>

	<div class="alert alert-info u-mb20" role="alert">
		<i class="fas fa-exclamation"></i>
		<p><?= tohtml(_("A scenario switched off runs in simulation: it still detects, but it bans nobody, and its current bans are lifted.")) ?></p>
	</div>

	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(_("Scenario")) ?></div>
			<div class="units-table-cell"></div>
			<div class="units-table-cell"><?= tohtml(_("Description")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Status")) ?></div>
		</div>

		<?php
			$i = 0;
				foreach ($data as $name => $value) {
					++$i;
					$on = ($value["STATE"] ?? "") === "on";
					$q = ["scenario" => $name, "state" => $on ? "off" : "on", "token" => $_SESSION["token"]];
					$label = $on ? _("Switch off") : _("Switch on");
					$confirm = $on
						? sprintf(_("Switch off scenario %s? It keeps detecting but bans nobody, and its current bans are lifted."), $name)
						: sprintf(_("Switch on scenario %s? It bans again from now on."), $name);
					?>
			<div class="units-table-row js-unit">
				<div class="units-table-cell units-table-heading-cell u-text-bold">
					<span class="u-hide-desktop"><?= tohtml(_("Scenario")) ?>:</span>
					<?= tohtml($name) ?>
				</div>
				<div class="units-table-cell">
					<ul class="units-table-row-actions">
						<li class="units-table-row-action" data-key-action="js">
							<a
								class="units-table-row-action-link data-controls js-confirm-action"
								href="/edit/firewall/crowdsec/?<?= tohtml(http_build_query($q)) ?>"
								title="<?= tohtml($label) ?>"
								data-confirm-title="<?= tohtml($label) ?>"
								data-confirm-message="<?= tohtml($confirm) ?>"
							>
								<i class="fas <?= $on ? "fa-pause icon-highlight" : "fa-play icon-green" ?>"></i>
								<span class="u-hide-desktop"><?= tohtml($label) ?></span>
							</a>
						</li>
					</ul>
				</div>
				<div class="units-table-cell">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Description")) ?>:</span>
					<?= tohtml($value["DESCRIPTION"] ?? "") ?>
				</div>
				<div class="units-table-cell u-text-bold u-text-center-desktop">
					<span class="u-hide-desktop"><?= tohtml(_("Status")) ?>:</span>
					<?php if ($on) { ?>
						<i class="fas fa-circle-check icon-green"></i> <?= tohtml(_("Active")) ?>
					<?php } else { ?>
						<i class="fas fa-circle-pause icon-orange"></i> <?= tohtml(_("Simulation")) ?>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
	</div>

	<div class="units-table-footer">
		<p>
			<?php
						if ($i == 0) {
							echo tohtml(_("CrowdSec listed no scenario."));
						} else {
							printf(ngettext("%d scenario", "%d scenarios", $i), $i);
						}
				?>
		</p>
	</div>

</div>
