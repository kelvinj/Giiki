<?php
$pages = $g->get_pages();
include 'giiki/theme/_header.php';
?>

    <div id="wrapper" class="wat-cf">
		<div id="main">
			<div class="block" id="">
				<div class="content">
					<h2 class="title">Pages</h2>
					<div class="inner">
						<!-- messages //-->
						<?php app_show_feedback(); ?>

						<table class="table">
						<tr>
							<th class="first">
								Page
							</th>
							<th class="last">
						</tr>

						<tr>
							<td class="first" colspan="2">
								<a href="/">home</a></td>
						</tr>
						<?php
						foreach ($pages as $p):
							if ($p->name == 'index.html') continue;
							$indent = (int) substr_count($p->name, '/');
						?>
							<tr>
								<td class="first" colspan="2">
									<a href="/<?php h($p->name) ?>" class="indent-<?php echo $indent ?>"><?php h(substr($p->name, 0, -5)); ?></a></td>
							</tr>
						<?php endforeach; ?>
						</table>
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