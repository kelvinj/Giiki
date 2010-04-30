<?php
$g->enforce_admin();
if (http_is_post()) {
	if ($_POST['form_action'] == 'newuser') {
		if ($user = $g->add_user($_POST)) {
			Fu_Feedback::set_flash('user added');
			http_redirect(http_request_uri());
		}
	}
	if ($_POST['form_action'] == 'delete') {
		$num_deletions = 0;
		foreach ((array) $_POST['users'] as $uid) {
			$user = $g->get_user($uid);
			if ($user->delete($uid)) {
				$num_deletions++;
			}
		}

		if ($num_deletions) {
			$msg = $num_deletions==1 ? '1 user deleted' : "$num_deletions users deleted";
			Fu_Feedback::set_flash($msg);
		}
		else {
			Fu_Feedback::set_flash('No users selected', 'info');
		}

		http_redirect(http_request_uri());
	}
}


$users = $g->get_users();
$current_user = $g->get_logged_in_user();
include 'giiki/theme/_header.php';
?>

    <div id="wrapper" class="wat-cf">
		<div id="main">
			<div class="block" id="">
				<div class="content">
					<h2 class="title">Manage Users</h2>
					<div class="inner">
						<!-- messages //-->
						<?php app_show_feedback(); ?>

						<form action="" method="post">
						<table class="table">
						<tr>
							<th class="first">
								<input type="checkbox" class="checkbox toggle" />
							</th>
							<th>Name</th>
							<th>Email</th>
							<th class="last">Admin?</th>
						</tr>

						<?php
						foreach ($users as $u):
						?>
							<tr>
								<td class="first">
									<?php if ($u->id != $current_user->id): ?>
									<input type="checkbox" class="checkbox" name="users[]" value="<?php h($u->id) ?>" />
									<?php endif; ?>
								</td>
								<td><?php h($u->name); ?></td>
								<td><?php h($u->email); ?></td>
								<td><?php h($u->is_admin ? 'Yes' : 'No'); ?></td>
							</tr>
						<?php endforeach; ?>
						</table>
						<div class="actions-bar wat-cf">
							<div class="actions">
								<input type="hidden" name="form_action" value="delete" />
								<button type="submit" class="button">
									<img alt="Delete" src="/giiki/theme/assets/cross.png"> Delete
								</button>
							</div>
						</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div id="sidebar">
			<div class="block">
				<h3>New User</h3>
				<div class="sidebar-block">
					<form action="" method="post">
						<p>
							<label for="username">Name <span>required</span></label><br />
							<input type="text" name="name" id="username" value="<?php h($_POST['username']); ?>" />
						</p>
						<p>
							<label for="email">Email <span>required</span></label><br />
							<input type="text" name="email" id="email" value="<?php h($_POST['email']); ?>" />
						</p>
						<p>
							<label for="password">Password <span>required</span></label><br />
							<input type="password" name="password" id="password" value="<?php h($_POST['password']); ?>" />
						</p>
						<p>
							<label for="is_admin">Admin?</label>
							<input type="checkbox" name="is_admin" value="1" id="is_admin" <?php h($_POST['is_checked'] ? 'checked="checked"' : ''); ?> />
						</p>
						<div class="group navform wat-cf">
							<input type="hidden" name="form_action" value="newuser" />
							<button type="submit" class="button">
							  <img alt="Save" src="/giiki/theme/assets/tick.png"> Save
							</button>
						<!--<a class="button" href="#header">-->
						<!--  <img alt="Cancel" src="/giiki/theme/assets/cross.png"> Cancel-->
						<!--</a>-->
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