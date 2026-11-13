<?php
$dns_state_labels = [
	"ok" => ["fa-circle-check icon-green", _("ok")],
	"refused" => ["fa-triangle-exclamation icon-red", _("key refused")],
	"error" => ["fa-triangle-exclamation icon-orange", _("not reachable")],
	"unchecked" => ["fa-circle-question icon-dim", _("not checked yet")],
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

	<h1 class="u-mt20 u-mb20"><?= tohtml(_("DNS Providers")) ?></h1>
	<?php show_alert_message($_SESSION); ?>

	<?php if (!empty($accounts)) { ?>
		<div class="units-table js-units-container u-mb20">
			<div class="units-table-header">
				<div class="units-table-cell"><?= tohtml(_("Account")) ?></div>
				<div class="units-table-cell"></div>
				<div class="units-table-cell"><?= tohtml(_("Provider")) ?></div>
				<div class="units-table-cell"><?= tohtml(_("Key")) ?></div>
				<div class="units-table-cell"><?= tohtml(_("Status")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(_("Zones")) ?></div>
			</div>
			<?php foreach ($accounts as $name => $a) {
				$name = (string) $name;
				[$icon, $label] = $dns_state_labels[$a["STATE"] ?? "unchecked"] ?? $dns_state_labels["unchecked"];
				?>
				<div class="units-table-row js-unit">
					<div class="units-table-cell units-table-heading-cell u-text-bold">
						<span class="u-hide-desktop"><?= tohtml(_("Account")) ?>:</span>
						<?= tohtml($name) ?>
					</div>
					<div class="units-table-cell">
						<ul class="units-table-row-actions">
							<li class="units-table-row-action" data-key-action="href">
								<a
									class="units-table-row-action-link"
									href="/list/dns/?<?= tohtml(http_build_query(["providers" => 1, "check" => $name, "token" => $_SESSION["token"]])) ?>"
									title="<?= tohtml(_("Check")) ?>"
								>
									<i class="fas fa-arrows-rotate icon-blue"></i>
									<span class="u-hide-desktop"><?= tohtml(_("Check")) ?></span>
								</a>
							</li>
							<li class="units-table-row-action shortcut-delete" data-key-action="js">
								<a
									class="units-table-row-action-link data-controls js-confirm-action"
									href="/list/dns/?<?= tohtml(http_build_query(["providers" => 1, "delete" => $name, "token" => $_SESSION["token"]])) ?>"
									title="<?= tohtml(_("Delete")) ?>"
									data-confirm-title="<?= tohtml(_("Delete")) ?>"
									data-confirm-message="<?= tohtml(sprintf(_("Are you sure you want to delete the account %s? Nothing changes at the provider."), $name)) ?>"
								>
									<i class="fas fa-trash icon-red"></i>
									<span class="u-hide-desktop"><?= tohtml(_("Delete")) ?></span>
								</a>
							</li>
						</ul>
					</div>
					<div class="units-table-cell">
						<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Provider")) ?>:</span>
						<?= tohtml($a["PROVIDER"] ?? "") ?>
						<?php if (($a["CONTEXT"] ?? "") !== "") { ?>
							<span class="u-text-muted">(<?= tohtml(sprintf(_("context %s"), $a["CONTEXT"])) ?>)</span>
						<?php } ?>
					</div>
					<div class="units-table-cell">
						<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Key")) ?>:</span>
						<code><?= tohtml($a["KEY"] ?? "") ?></code>
					</div>
					<div class="units-table-cell">
						<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Status")) ?>:</span>
						<i class="fas <?= tohtml($icon) ?> u-mr5"></i><?= tohtml($label) ?>
						<?php if (($a["CHECKED"] ?? "") !== "") { ?>
							<span class="u-text-muted u-text-small"><?= tohtml(date("Y-m-d H:i", (int) $a["CHECKED"])) ?></span>
						<?php } ?>
						<?php if (($a["STATE"] ?? "") !== "ok" && ($a["MESSAGE"] ?? "") !== "") { ?>
							<br><span class="u-text-small"><?= tohtml($a["MESSAGE"]) ?></span>
						<?php } ?>
					</div>
					<div class="units-table-cell u-text-center-desktop">
						<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Zones")) ?>:</span>
						<?= tohtml($a["ZONES"] ?? "0") ?>
					</div>
				</div>
			<?php } ?>
		</div>
	<?php } else { ?>
		<div class="alert alert-info u-mb20" role="alert">
			<i class="fas fa-exclamation"></i>
			<p><?= tohtml(_("No provider account yet. Without one every zone lists its records to enter by hand.")) ?></p>
		</div>
	<?php } ?>

	<form method="post" action="/list/dns/?providers=1" class="form-container">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<h2 class="u-mb10"><?= tohtml(_("Add Account")) ?></h2>
		<p class="hint u-mb20"><?= tohtml(_("The key is checked with the provider before the account is saved. It is stored for root only and is not part of any backup.")) ?></p>
		<div class="u-mb10">
			<label for="v_account" class="form-label"><?= tohtml(_("Account")) ?></label>
			<input type="text" class="form-control" name="v_account" id="v_account" required pattern="[a-z0-9][a-z0-9_\-]{0,31}" placeholder="internetx">
			<small class="hint"><?= tohtml(_("Lower case letters, digits, - and _, up to 32 characters.")) ?></small>
		</div>
		<div class="u-mb10">
			<label for="v_provider" class="form-label"><?= tohtml(_("Provider")) ?></label>
			<select class="form-select" name="v_provider" id="v_provider">
				<?php foreach ($drivers as $d) { ?>
					<option value="<?= tohtml($d) ?>"><?= tohtml($d === "autodns" ? "InterNetX AutoDNS" : $d) ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="u-mb10">
			<label for="v_key" class="form-label"><?= tohtml(_("API Key")) ?></label>
			<input type="password" class="form-control" name="v_key" id="v_key" required autocomplete="off">
			<small class="hint"><?= tohtml(_("InterNetX: a key from User management > API with the rights DNS zone query, 0202002 (zone stream) and DNS zone update (bulk). Leave DNS zone update itself off.")) ?></small>
		</div>
		<div class="u-mb20">
			<label for="v_context" class="form-label"><?= tohtml(_("Context")) ?></label>
			<input type="text" class="form-control" name="v_context" id="v_context" value="4" inputmode="numeric" pattern="[0-9]{1,9}">
		</div>
		<button type="submit" class="button">
			<i class="fas fa-circle-plus icon-green"></i><?= tohtml(_("Add Account")) ?>
		</button>
	</form>

</div>
