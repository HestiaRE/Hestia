<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/mail/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
		<div class="toolbar-right"></div>
	</div>
</div>

<div class="container">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30"><?= tohtml(_("DNS Records")) ?></h1>

	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(_("Record")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Type")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Priority")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("TTL")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("IP or Value")) ?></div>
		</div>

		<?php foreach ($records as $record) { ?>
			<div class="units-table-row js-unit">
				<div class="units-table-cell">
					<label class="u-hide-desktop u-text-bold"><?= tohtml(_("Record")) ?>:</label>
					<input type="text" class="form-control" value="<?= tohtml($record["NAME"]) ?>">
				</div>
				<div class="units-table-cell u-text-bold u-text-center-desktop">
					<span class="u-hide-desktop"><?= tohtml(_("Type")) ?>:</span>
					<?= tohtml($record["TYPE"]) ?>
				</div>
				<div class="units-table-cell u-text-bold u-text-center-desktop">
					<span class="u-hide-desktop"><?= tohtml(_("Priority")) ?>:</span>
					<?= tohtml($record["PRIORITY"]) ?>
				</div>
				<div class="units-table-cell u-text-bold u-text-center-desktop">
					<span class="u-hide-desktop"><?= tohtml(_("TTL")) ?>:</span>
					<?= tohtml($record["TTL"]) ?>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<label class="u-hide-desktop u-text-bold"><?= tohtml(_("IP or Value")) ?>:</label>
					<input type="text" class="form-control" value="<?= tohtml($record["VALUE"]) ?>">
				</div>
			</div>
		<?php } ?>
	</div>

</div>
