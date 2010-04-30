<?php
if (http_is_post()) {
	if ($_POST['form_action'] == 'newuser') {
		if ($user = $g->add_user($_POST)) {
			Fu_Feedback::set_flash('user added');
			http_redirect(http_request_uri());
		}
	}
}


$users = $g->get_users();
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

						<table class="table">
						<tr>
							<th class="first">Name</th>
							<th>Email</th>
							<th>Admin?</th>
							<th class="last"></th>
						</tr>

						<?php
						foreach ($users as $u):
						?>
							<tr>
								<td class="first"><?php h($u->name); ?></td>
								<td><?php h($u->email); ?></td>
								<td><?php h($u->is_admin ? 'Yes' : 'No'); ?></td>
								<td class="last">
									<a href="?userid=<?php h($u->id); ?>">
										edit</a>
									|
									<a href="?delete&userid=<?php h($u->id); ?>">
										delete</a>
							</tr>
						<?php endforeach; ?>
						</table>
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
		$('#pagename').focus(function(){this.select();});
    });
// ]]>
</script>

<?php include 'giiki/theme/_footer.php'; ?>