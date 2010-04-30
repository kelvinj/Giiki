<?php
$pages = $g->get_pages();

if (http_is_post()) {
	if ($_POST['form_action'] == 'changepassword') {
		if ($g->change_password($_POST['current_password'], $_POST['new_password'])) {
			Fu_Feedback::set_flash('new password saved');
			http_redirect(http_request_uri());
		}
	}

	if ($_POST['form_action'] == 'savesettings') {
		if ($g->save_settings($_POST['settings'])) {
			Fu_Feedback::set_flash('settings saved');
			http_redirect(http_request_uri());
		}
	}
}


$default_settings = array(
	'editor' => array(
		'normal' => 'Normal textarea',
		'tinymce' => 'TinyMCE'
	)
);

$current_user = $g->get_logged_in_user();
$settings = $current_user->settings();

include 'giiki/theme/_header.php';
?>

    <div id="wrapper" class="wat-cf">
		<div id="main">
			<?php if (http_is_get()): ?>
			<div class="block" id="">
				<!-- messages //-->
				<?php app_show_feedback(); ?>
			</div>
			<?php endif; ?>

			<div class="block" id="">
				<div class="content">
					<h2 class="title">Change Password</h2>
					<div class="inner">
						<!-- messages //-->
						<?php
						if ($_POST['form_action'] == 'changepassword') {
							app_show_feedback();
						}
						?>
						<form action="" method="post" class="form">
						<div class="group">
							<label class="label" for="current_password">Current password</label>
							<input class="text_field" type="password" name="current_password" id="current_password" />
							<span class="description">Confirm your current password</span>
						</div>
						<div class="group">
							<label class="label" for="new_password">New password</label>
							<input class="text_field" type="password" name="new_password" id="new_password" />
							<span class="description">You will have to use this password the next time you log in</span>
						</div>
						<div class="group navform wat-cf">
							<input type="hidden" name="form_action" value="changepassword" />
							<button class="button" type="submit">
								<img src="/giiki/theme/assets/tick.png" alt="Save" /> Save
							</button>
						</div>
						</form>
					</div>
				</div>
			</div>

			<div class="block" id="">
				<div class="content">
					<h2 class="title">Other Settings</h2>
					<div class="inner">
						<!-- messages //-->
						<?php
						if ($_POST['form_action'] == 'savesettings') {
							app_show_feedback();
						}
						?>
						<form action="" method="post" class="form">
						<div class="group">
							<label class="label" for="editor">Page editor</label>
							<select name="settings[editor]" id="editor">
								<?php
								foreach ($default_settings['editor'] as $k => $v) {
									$s = $settings->editor==$k ? 'selected="selected"' : '';
									printf('<option value="%s" %s>%s</option>', $k, $s, $v);
								}
								?>
							</select>
						</div>
						<div class="group">
							<label class="label" for="show_history">
								Always show history
								<input type="checkbox" class="checkbox" name="settings[show_history]" value="1" id="show_history" <?php echo $settings->show_history ? 'checked="checked"' : '' ?> />
							</label>
						</div>
						<div class="group">
							<label class="label" for="fullscreen_edit">
								Edit in full screen mode? (if the editor supports it)
								<input type="checkbox" class="checkbox" name="settings[fullscreen_edit]" value="1" id="fullscreen_edit" <?php echo $settings->fullscreen_edit ? 'checked="checked"' : '' ?> />
							</label>
							<label class="label" for="remain_edit_mode">
								Remain in edit mode after save?
								<input type="checkbox" class="checkbox" name="settings[remain_edit_mode]" value="1" id="remain_edit_mode" <?php echo $settings->remain_edit_mode ? 'checked="checked"' : '' ?> />
							</label>
						</div>

						<div class="group navform wat-cf">
							<input type="hidden" name="form_action" value="savesettings" />
							<button class="button" type="submit">
								<img src="/giiki/theme/assets/tick.png" alt="Save" /> Save
							</button>
						</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div id="sidebar">
			<div class="block">
				<h3>New Page</h3>
				<div class="sidebar-block">
					<form action="" method="post">
						<p>
							<label for="pagename">Page name (inc. directory)</label><br />
							<input type="text" name="pagename" id="pagename" placeholder="example" />.html
						</p>
						<div class="group navform wat-cf">
							<input type="hidden" name="form_action" value="newpage" />
							<button type="submit" class="button">
							  <img alt="Save" src="/giiki/theme/assets/tick.png" /> Create
							</button>
						</div>
					</form>
				</div>
			</div>

<script type="text/javascript" charset="utf-8">
// <![CDATA[
    $(document).ready(function() {
        $('.table :checkbox.toggle').each(function(i, toggle) {
            $(toggle).change(function(e) {
                $(toggle).parents('table:first').find(':checkbox:not(.toggle)').each(function(j, checkbox) {
                    checkbox.checked = !checkbox.checked;
                })
            });
        });
		$('#pagename').focus(function(){this.select();});
    });
// ]]>
</script>

<?php include 'giiki/theme/_footer.php'; ?>