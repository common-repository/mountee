<div class="wrap mountee-wrap">
	<h1>Mountee</h1>

	<p class="strong">Thanks for installing Mountee 3.</p>

	<?php if (isset($theme_error_message)){ ?>
		<p class="error-message"><?=$theme_error_message?></p>
	<?php } ?>

	<p><a href="mountee://add_site?title=<?=urlencode($site_name)?>&url=<?=urlencode($mountee_url)?>&username=<?=urlencode($current_user->user_login)?>">Click here</a> to open Mountee and add the site automatically</p>

	<p>Or use the following information to add your site to the Mountee 3 app:</p>

	<p class="param"><strong>Mountee URL:</strong> <?=$mountee_url?></p>
	<p class="param"><strong>Username:</strong> <?=$current_user->user_login?></p>
	<p class="param"><strong>Password:</strong> The password you use to login to the control panel</p>

	<form class="settings" method="post" action="options.php">
		<?php settings_fields( 'mountee_options' ); ?>
		<?php do_settings_sections( 'mountee-main-page' );?>
		<?php submit_button(); ?>
	</form>
</div>