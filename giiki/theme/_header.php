<?php
$nav = array();

$path = http_path();
$nav['/'] = 'wiki';
$nav['/pages.php'] = 'view all pages';

if ($g->is_admin_user()) {
	$nav['/users.php'] = 'manage users';
}

$current_user = $g->get_logged_in_user();
?>
<!doctype html>
<html dir="ltr" lang="en-US">
<head>
    <title><?php echo $g->get_page_name(true); ?> ; <?php echo $g->get_site_name(); ?></title>

	<link rel="stylesheet" href="/giiki/theme/assets/base.css" type="text/css" media="screen"/>
    <link rel="stylesheet" id="current-theme" href="/giiki/theme/assets/style.css" type="text/css" media="screen"/>
    <link rel="stylesheet" id="current-theme" href="/giiki/theme/assets/style.css" type="text/css" media="screen"/>
    <link rel="stylesheet" id="current-theme" href="/giiki/theme/assets/smoothness/jquery-ui-1.8.custom.css" type="text/css" media="screen"/>
    <link rel="stylesheet" id="current-theme" href="/giiki/theme/assets/print.css" type="text/css" media="print"/>

	<script type="text/javascript" charset="utf-8" src="/giiki/theme/js/jquery.js"></script>
	<script type="text/javascript" charset="utf-8" src="/giiki/theme/js/jquery-ui-1.8.custom.min.js"></script>
</head>
<body>
<div id="container">
	<div id="header">
		<h1><a href="/"><?php h($g->get_site_name()); ?></a></h1>

		<input id="search" placeholder="Search…" />
		<script type="text/javascript">
			$(function() {
				$("#search").autocomplete({
					source: "/search.php",
					minLength: 2,
					select: function(event, ui) {
						if (ui.item) {
							document.location.href = "/"+ui.item.url;
						}
					}
				});
			});
		</script>

      <div id="user-navigation">
        <ul class="wat-cf">
          <li><a href="/settings.php">Settings</a></li>
		  <li>Logged in as <?php h($current_user->name) ?></li>
          <li><a class="logout" href="/logout.php">Logout</a></li>
        </ul>
      </div>
      <div id="main-navigation">
        <ul class="wat-cf">
		<?php
		$i=0;
		foreach ($nav as $k => $v){
			$class = array();
			if ($i==0) $class[] = 'first';
			if ($i==0 && substr($path, -5) == '.html') $class[] = 'active';
			else if (http_path() == $k) $class[] = 'active';

			printf('<li class="%s"><a href="%s">%s</a></li>', implode(' ', $class), $k, h($v, true));
			$i++;
		}
		?>
        </ul>
      </div>
    </div>