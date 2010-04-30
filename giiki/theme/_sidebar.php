<?php
$crumbs = $g->get_breadcrumbs();
$children = $g->get_child_pages();
$db_page = $g->get_db_page();
?>
			<div class="block">
				<h3>Breadcrumbs</h3>
				<ul class="navigation">
				<?php
				foreach ($crumbs as $k => $v) {
					printf('<li><a href="%s">%s</a></li>', $k, $v);
				}
				printf('<li><a href="%s"><b>%s</b></a></li>', http_request_uri(), $g->get_page_name());
				foreach ($children as $k => $v) {
					$indent = (int) substr_count(str_replace('/'.$g->get_page_name(), '', $k), '/');
					printf('<li><a href="%s" class="indent-%s">%s</a></li>', $k, $indent, $v);
				}
				?>
				</ul>
			</div>
			<div class="block">
				<h3>Sidebar</h3>
				<ul class="navigation">
					<li><a href="?edit" title="Edit <?php h($g->get_page()) ?>">Edit Page</a></li>
					<li><a href="#history" id="history-btn">History / Commits</a></li>
				</ul>
			</div>

			<div class="block">
				<h3>New Page</h3>
				<div class="sidebar-block">
					<form action="" method="post">
						<p>
							<label for="pagename">Page name (inc. directory)</label><br />
							<?php
							$folder = $db_page->name == 'index.html' ? '' : substr($db_page->name, 0, -5).'/';
							?>
							<input type="text" name="pagename" id="pagename" value="<?php h($folder) ?>" />.html
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