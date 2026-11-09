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

	<div class="alert alert-info u-mb20" role="alert">
		<i class="fas fa-exclamation"></i>
		<p><?= tohtml(_("No provider account yet. Accounts come with the first provider driver; until then every zone lists its records to enter by hand.")) ?></p>
	</div>

</div>
