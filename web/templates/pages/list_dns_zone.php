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
	"exception" => ["fa-thumbtack icon-blue", _("exception")],
	"missing" => ["fa-circle-plus icon-orange", _("missing")],
	"different" => ["fa-triangle-exclamation icon-orange", _("different")],
	"cname" => ["fa-triangle-exclamation icon-orange", _("CNAME")],
	"wildcard" => ["fa-triangle-exclamation icon-orange", _("wildcard")],
	"delegated" => ["fa-circle-pause icon-dim", _("delegated")],
	"unchecked" => ["fa-circle-question icon-dim", _("unchecked")],
	"shadowed" => ["fa-circle-minus icon-dim", _("shadowed")],
];
// ok judges SPF and DMARC by effect, so an ok row can hold another value than the derived one: shown then as well.
$dns_actual_differs = function (array $r): bool {
	$norm = fn (string $v) => strtolower((string) preg_replace('/\s+/', "", $v));
	return $r["ACTUAL"] !== "" && !in_array($norm($r["ACTUAL"]), [$norm($r["VALUE"]), $norm($r["PRIORITY"] . $r["VALUE"])], true);
};
// The actions on a record the user added: forget it (it stays at the provider), or hand it to the orphan removal.
$dns_added_actions = function (array $r, string $owner, string $object, bool $remove) use ($v_zone): void {
	?>
	<form method="post" class="u-mt10" action="/list/dns/?<?= tohtml(http_build_query(["zone" => $v_zone, "added" => 1])) ?>">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="v_owner" value="<?= tohtml($owner) ?>">
		<input type="hidden" name="v_object" value="<?= tohtml($object) ?>">
		<input type="hidden" name="v_record" value="<?= tohtml($r["NAME"] ?? $r["RECORD"]) ?>">
		<input type="hidden" name="v_type" value="<?= tohtml($r["TYPE"]) ?>">
		<input type="hidden" name="v_value" value="<?= tohtml($r["VALUE"]) ?>">
		<input type="hidden" name="v_priority" value="<?= tohtml($r["PRIORITY"]) ?>">
		<button type="submit" name="v_action" value="forget" class="button button-secondary" title="<?= tohtml(_("HestiaRE forgets the record, which stays at the provider as a foreign one.")) ?>">
			<i class="fas fa-thumbtack icon-blue"></i><?= tohtml(_("Forget")) ?>
		</button>
		<?php if ($remove) { ?>
			<button type="submit" name="v_action" value="remove" class="button button-secondary" title="<?= tohtml(_("Remove the record from the provider. A preview shows the write first.")) ?>">
				<i class="fas fa-trash icon-red"></i><?= tohtml(_("Remove from provider")) ?>
			</button>
		<?php } ?>
	</form>
	<?php
};
// The records of the provider in one table: name, type, priority, TTL, value and what the driver said about them;
// $after adds to a row's value cell.
$dns_provider_rows = function (array $rows, ?callable $after = null): void {
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
					<?php if ($after !== null) {
						$after($r);
					} ?>
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
	<?php show_alert_message($_SESSION); ?>

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
			<p><?= tohtml(sprintf(ngettext("Compared with account %2\$s just now: %1\$d record differs from what HestiaRE derives. Sync shows a section's writes before anything changes.", "Compared with account %2\$s just now: %1\$d records differ from what HestiaRE derives. Sync shows a section's writes before anything changes.", $v_differences), $v_differences, $v_account)) ?></p>
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
		<?php
	$sync_query = ["zone" => $v_zone, "sync" => $s["kind"], "object" => $s["object"], "owner" => $s["owner"]];
	// www can follow the apex again where it has records of its own that equal what the apex derives; the preview
	// says when the apex is not in line yet.
	$www = array_filter($s["rows"], fn ($r) => $r["NAME"] === "www" && in_array($r["TYPE"], ["A", "AAAA"], true));
	$apex = array_column(array_filter($s["rows"], fn ($r) => $r["NAME"] === "@" && in_array($r["TYPE"], ["A", "AAAA"], true)), "VALUE", "TYPE");
	$followable = $v_checked && $www && !array_filter($www, fn ($r) => ($r["NOTE"] ?? "") === "follows the apex (www include)"
		|| !empty($r["EXCEPTION"]) || ($apex[$r["TYPE"]] ?? null) !== $r["VALUE"]);
	$unsynced = $v_checked && array_filter($s["rows"], fn ($r) => !in_array($r["STATUS"] ?? "ok", ["ok", "exception"], true));
	if ($unsynced || $followable) { ?>
			<p class="u-mb10">
				<?php if ($unsynced) { ?>
					<a class="button button-secondary" href="/list/dns/?<?= tohtml(http_build_query($sync_query)) ?>">
						<i class="fas fa-arrows-rotate icon-blue"></i><?= tohtml(_("Sync to provider")) ?>
					</a>
				<?php } ?>
				<?php if ($followable) { ?>
					<a class="button button-secondary" href="/list/dns/?<?= tohtml(http_build_query($sync_query + ["confirm" => "follow"])) ?>" title="<?= tohtml(_("Switch the www include on: the provider serves the apex addresses at www, and the own www records go.")) ?>">
						<i class="fas fa-link icon-blue"></i><?= tohtml(_("www follows the apex")) ?>
					</a>
				<?php } ?>
			</p>
		<?php } ?>
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
						<?php if (!empty($r["ADDED"])) { ?>
							<br><span class="u-text-muted u-text-small" title="<?= tohtml(_("Added on this page, not derived: HestiaRE writes it as it is.")) ?>"><?= tohtml(_("added")) ?></span>
						<?php } ?>
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
						<?php if (!$v_checked && !empty($r["ADDED"])) {
							$dns_added_actions($r, $s["owner"], $s["object"], false);
						} ?>
					</div>
					<?php if ($v_checked) {
						[$icon, $label] = $dns_status_labels[$r["STATUS"]] ?? ["fa-circle-question icon-dim", $r["STATUS"]];
						?>
						<div class="units-table-cell u-text-break">
							<span class="u-hide-desktop u-text-bold"><?= tohtml(_("At the provider")) ?>:</span>
							<i class="fas <?= tohtml($icon) ?> u-mr5" title="<?= tohtml($label) ?>"></i><span class="u-text-bold"><?= tohtml($label) ?></span>
							<?php if ($r["ACTUAL"] !== "" && ($r["STATUS"] !== "ok" || $dns_actual_differs($r))) { ?>
								<br><?= tohtml($r["ACTUAL"]) ?>
							<?php } ?>
							<?php if (($r["NOTE"] ?? "") !== "") { ?>
								<br><span class="u-text-muted u-text-small"><?= tohtml($r["NOTE"]) ?></span>
							<?php } ?>
							<?php
							// Kept with what the provider holds as shown here; a later change there shows as a drift.
							$keepable = in_array($r["STATUS"], ["different", "missing", "cname", "wildcard"], true) && empty($r["EXCEPTION"])
								&& empty($r["ADDED"]);
						if ($keepable || !empty($r["EXCEPTION"])) { ?>
								<form method="post" class="u-mt10" action="/list/dns/?<?= tohtml(http_build_query(["zone" => $v_zone, "exception" => 1])) ?>">
									<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
									<input type="hidden" name="v_owner" value="<?= tohtml($s["owner"]) ?>">
									<input type="hidden" name="v_record" value="<?= tohtml($r["NAME"]) ?>">
									<input type="hidden" name="v_type" value="<?= tohtml($r["TYPE"]) ?>">
									<input type="hidden" name="v_anchor" value="<?= tohtml(($s["kind"] === "mail" ? "obj-mail-" : "obj-") . $s["object"]) ?>">
									<?php if ($keepable) { ?>
										<?php foreach ($r["FOUND"] ?? [] as $f) { ?>
											<input type="hidden" name="v_values[]" value="<?= tohtml($f) ?>">
										<?php } ?>
										<input type="hidden" name="v_action" value="keep">
										<button type="submit" class="button button-secondary" title="<?= tohtml(_("Accept what the provider holds instead of the derived value. Sync then leaves it alone.")) ?>">
											<i class="fas fa-thumbtack icon-blue"></i><?= tohtml(empty($r["FOUND"]) ? _("Keep without this record") : _("Keep as exception")) ?>
										</button>
									<?php } else { ?>
										<input type="hidden" name="v_action" value="lift">
										<button type="submit" class="button button-secondary" title="<?= tohtml(_("Compare with the derived value again. Sync then writes that.")) ?>">
											<i class="fas fa-xmark icon-red"></i><?= tohtml(_("Lift exception")) ?>
										</button>
									<?php } ?>
								</form>
							<?php } ?>
							<?php
						$row_query = $sync_query + ["record" => $r["NAME"], "type" => $r["TYPE"]];
						$applicable = in_array($r["STATUS"], ["missing", "different", "cname", "wildcard"], true);
						// Offered for an SPF record other than the standard one, whatever its status.
						$normalizable = $r["TYPE"] === "TXT" && empty($r["EXCEPTION"]) && empty($r["ADDED"]) && preg_match('/^v=spf1\b/i', $r["VALUE"]) === 1
							&& ($r["FOUND"] ?? []) !== [] && ($r["FOUND"] ?? []) !== ["v=spf1 a mx ~all"];
						if ($applicable || $normalizable) { ?>
								<p class="u-mt10">
									<?php if ($applicable) { ?>
										<a class="button button-secondary" href="/list/dns/?<?= tohtml(http_build_query($row_query)) ?>" title="<?= tohtml(_("Sync this record alone. A preview shows the writes first.")) ?>">
											<i class="fas fa-arrows-rotate icon-blue"></i><?= tohtml(_("Apply")) ?>
										</a>
									<?php } ?>
									<?php if ($normalizable) { ?>
										<a class="button button-secondary" href="/list/dns/?<?= tohtml(http_build_query($row_query + ["confirm" => "normalize"])) ?>" title="<?= tohtml(_("Put the SPF record back to v=spf1 a mx ~all, without addresses or includes. A preview shows the writes first.")) ?>">
											<i class="fas fa-broom icon-blue"></i><?= tohtml(_("Normalize")) ?>
										</a>
									<?php } ?>
								</p>
							<?php } ?>
							<?php if (!empty($r["ADDED"])) {
								$dns_added_actions($r, $s["owner"], $s["object"], ($r["FOUND"] ?? []) !== []);
							} ?>
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

	<?php foreach ($orphans as $o) { ?>
		<h2 class="u-mt20 u-mb10" id="<?= tohtml("obj-orphan-" . $o["object"]) ?>">
			<?= tohtml(_("Orphaned")) ?>: <?= tohtml($o["object"]) ?>
			<span class="u-text-muted u-text-small">
				<?php if ($o["owner"] !== $user_plain) { ?>
					<i class="fas fa-user icon-dim u-ml5"></i> <?= tohtml(sprintf(_("user %s"), $o["owner"])) ?>
				<?php } ?>
			</span>
		</h2>
		<p class="u-mb10"><?= tohtml(_("HestiaRE wrote these records, and nothing on this server derives them any longer: the domain or alias is gone, or the name no longer gets a record of this type.")) ?></p>
		<p class="u-mb10">
			<a class="button button-secondary" href="/list/dns/?<?= tohtml(http_build_query(["zone" => $v_zone, "sync" => "orphan", "object" => $o["object"], "owner" => $o["owner"]])) ?>">
				<i class="fas fa-trash icon-red"></i><?= tohtml(_("Remove from provider")) ?>
			</a>
		</p>
		<?php $dns_provider_rows($o["rows"], function (array $r) use ($o, $v_zone): void { ?>
			<form method="post" class="u-mt10" action="/list/dns/?<?= tohtml(http_build_query(["zone" => $v_zone, "orphan" => 1])) ?>">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="v_owner" value="<?= tohtml($o["owner"]) ?>">
				<input type="hidden" name="v_record" value="<?= tohtml($r["NAME"]) ?>">
				<input type="hidden" name="v_type" value="<?= tohtml($r["TYPE"]) ?>">
				<button type="submit" class="button button-secondary" title="<?= tohtml(_("The record stays at the provider, and HestiaRE no longer offers to remove it.")) ?>">
					<i class="fas fa-thumbtack icon-blue"></i><?= tohtml(_("Keep as foreign record")) ?>
				</button>
			</form>
		<?php }); ?>
	<?php } ?>

	<?php if ($v_checked) { ?>
		<h2 class="u-mt20 u-mb10"><?= tohtml(_("Other records at the provider")) ?></h2>
		<?php if ($foreign) { ?>
			<details class="u-mb20">
				<summary><?= tohtml(sprintf(ngettext("%d record no object on this server derives. HestiaRE never changes it.", "%d records no object on this server derives. HestiaRE never changes them.", count($foreign)), count($foreign))) ?></summary>
				<?php $dns_provider_rows($foreign, function (array $r) use ($v_zone): void {
					// The main IP, the www include and a blanked value are no record of the provider's own.
					if (($r["NOTE"] ?? "") !== "") {
						return;
					} ?>
					<form method="post" class="u-mt10" action="/list/dns/?<?= tohtml(http_build_query(["zone" => $v_zone, "added" => 1])) ?>">
						<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
						<input type="hidden" name="v_record" value="<?= tohtml($r["NAME"]) ?>">
						<input type="hidden" name="v_type" value="<?= tohtml($r["TYPE"]) ?>">
						<input type="hidden" name="v_value" value="<?= tohtml($r["VALUE"]) ?>">
						<input type="hidden" name="v_priority" value="<?= tohtml($r["PRIORITY"]) ?>">
						<input type="hidden" name="v_ttl" value="<?= tohtml($r["TTL"]) ?>">
						<button type="submit" name="v_action" value="adopt" class="button button-secondary" title="<?= tohtml(_("Keep the record as part of its section: the zone page compares it, a sync writes it where it is missing, and it travels with the backup.")) ?>">
							<i class="fas fa-hand-holding icon-blue"></i><?= tohtml(_("Adopt")) ?>
						</button>
					</form>
				<?php }); ?>
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

	<?php if ($v_unplaced) { ?>
		<h2 class="u-mt20 u-mb10"><?= tohtml(_("Added records without a section")) ?></h2>
		<p class="u-mb10"><?= tohtml(_("Their domain is gone from this server. No sync writes them until it is back.")) ?></p>
		<?php $dns_provider_rows(array_map(fn ($r) => $r + ["NAME" => $r["RECORD"]], $v_unplaced), function (array $r) use ($dns_added_actions, $user_plain): void {
			$dns_added_actions($r, $user_plain, "", false);
		}); ?>
	<?php } ?>

	<h2 class="u-mt20 u-mb10"><?= tohtml(_("Add a record")) ?></h2>
	<p class="u-mb10"><?= tohtml(_("A TXT or a CNAME of your own, in the section its name lies in. Other types are adopted from the provider's records above.")) ?></p>
	<form method="post" class="u-mb20" action="/list/dns/?<?= tohtml(http_build_query(["zone" => $v_zone, "added" => 1])) ?>">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="v_action" value="new">
		<div class="u-mb10">
			<label for="v_record" class="form-label"><?= tohtml(_("Record")) ?> <span class="optional">(<?= tohtml(sprintf(_("@ for %s"), $v_zone)) ?>)</span></label>
			<input type="text" class="form-control" name="v_record" id="v_record" required>
		</div>
		<div class="u-mb10">
			<label for="v_type" class="form-label"><?= tohtml(_("Type")) ?></label>
			<select class="form-select" name="v_type" id="v_type">
				<option value="TXT">TXT</option>
				<option value="CNAME">CNAME</option>
			</select>
		</div>
		<div class="u-mb10">
			<label for="v_value" class="form-label"><?= tohtml(_("IP or Value")) ?></label>
			<input type="text" class="form-control" name="v_value" id="v_value" required>
		</div>
		<div class="u-mb10">
			<label for="v_ttl" class="form-label"><?= tohtml(_("TTL")) ?> <span class="optional">(<?= tohtml(_("optional")) ?>)</span></label>
			<input type="text" class="form-control" name="v_ttl" id="v_ttl" placeholder="3600">
		</div>
		<button type="submit" class="button">
			<i class="fas fa-plus icon-green"></i><?= tohtml(_("Add record")) ?>
		</button>
	</form>

	<?php if (count($sections) > 1) { ?>
		<h2 class="u-mt20 u-mb10"><?= tohtml(_("Whole zone")) ?></h2>
		<textarea class="form-control u-console u-mb20" readonly rows="10"><?= tohtml($v_zone_text) ?></textarea>
	<?php } ?>

</div>
