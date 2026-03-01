<?php namespace A11yc; ?>

<ul>
	<li><a href="<?php echo A11YC_ICL_URL ?>index"><?php echo A11YC_LANG_ICL_TITLE ?></a></li>
	<li><a href="<?php echo A11YC_ICL_URL ?>edit&amp;is_sit=1"><?php echo A11YC_LANG_ICL_NEW_SITUATION ?></a></li>
	<li><a href="<?php echo A11YC_ICL_URL ?>edit"><?php echo A11YC_LANG_ICL_NEW ?></a></li>
	<li><a href="<?php echo A11YC_ICL_URL ?>view"><?php echo A11YC_LANG_ICL_TITLE_VIEW ?></a></li>

<?php if (empty(Model\Setting::fetch('is_waic_imported'))): ?>
	<li>
	<?php if (isset($icl_action_nonce) && ! empty($icl_action_nonce)): ?>
		<form action="<?php echo A11YC_ICL_URL ?>import" method="POST" style="display:inline;">
			<?php echo $icl_action_nonce ?>
			<button type="submit" class="button-link"><?php echo A11YC_LANG_ICL_IMPORT_WAIC ?></button>
		</form>
	<?php else: ?>
		<a href="<?php echo A11YC_ICL_URL ?>import"><?php echo A11YC_LANG_ICL_IMPORT_WAIC ?></a>
	<?php endif; ?>
	</li>
<?php endif; ?>
</ul>
