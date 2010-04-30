<?php
if (http_is_post()) {
	if ($_POST['form_action'] == 'login') {
		if ($g->login($_POST['email'], $_POST['password'], $_POST['fwd'])) {
			Fu_Feedback::set_flash('you are logged in');
			http_redirect(http_request_uri());
		}
	}
}
//Fu_Feedback::add_error('Some error');

?>
<!doctype html>
<html dir="ltr" lang="en-US">
<head>
    <title><?php echo $g->get_page_name(); ?> ; <?php echo $g->get_site_name(); ?></title>
    <link rel="stylesheet" href="/giiki/theme/assets/base.css" type="text/css" media="screen"/>
    <link rel="stylesheet" id="current-theme" href="/giiki/theme/assets/style.css" type="text/css" media="screen"/>
	<script type="text/javascript" charset="utf-8" src="/giiki/theme/js/jquery.js"></script>
</head>
<body>
<div id="container">

    <div id="wrapper" class="wat-cf">
			<div id="box">
			  <div class="block" id="block-login">
				<h2>Log in</h2>
				<div class="content login">
					<!-- messages //-->
					<?php app_show_feedback(); ?>
					<br />
				  <form action="" class="form login" method="post">
					<div class="group wat-cf">
					  <div class="left">
						<label class="label right" for="email">Email</label>
					  </div>
					  <div class="right">
						<input type="text" name="email" id="email" value="<?php h($_POST['email']); ?>" placeholder="joeblogs@example.com" />
					  </div>
					</div>
					<div class="group wat-cf">
					  <div class="left">
						<label class="label right" for="password">Password</label>
					  </div>
					  <div class="right">
						<input type="password" name="password" id="password" />
					  </div>
					</div>
					<div class="group navform wat-cf">
					  <div class="right">
						<input type="hidden" name="form_action" value="login" />
						<input type="hidden" name="fwd" value="<?php h($_REQUEST['fwd']) ?>" />
						<button class="button" type="submit">
						  <img src="/giiki/theme/assets/key.png" alt="Save"> Login
						</button>
					  </div>
					</div>
				  </form>
				</div>
			</div>



<script type="text/javascript" charset="utf-8">
// <![CDATA[
    $(document).ready(function() {
		$('#email').focus();
    });
// ]]>
</script>

	</div>

</div>

<script type="text/javascript" charset="utf-8" src="/giiki/theme/js/jquery.localscroll.js"></script>
<script type="text/javascript" charset="utf-8" src="/giiki/theme/js/jquery.scrollto.js"></script>

</body>
</html>