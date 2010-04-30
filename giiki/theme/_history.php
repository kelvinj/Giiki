<?php
include_once 'giiki/lib/geshi.php';

$history = $g->get_history();
$current_user = $g->get_logged_in_user();
$settings = $current_user->settings();
?>
<div class="block" id="history">
	<div class="content">
			<?php if ($history): ?>
				<table class="table">
				<tr>
					<th class="first">Commit</th>
					<th>Time</th>
					<th>Author</th>
					<th>Message</th>
					<th class="last"></th>
				</tr>
				<?php
				foreach ($history as $h):
					$geshi = new GeSHi($h['diff'], 'diff');
					//$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
					$geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
				?>
					<tr class="details">
						<td class="commit">
							<a href="javascript:void(0);" title="show: <?php h($h['commit']); ?>"><?php h(substr($h['commit'], 0, 7)); ?></a>
						</td>
						<td><?php h($h['date']); ?></td>
						<td>
							<img src="<?php echo $h['avatar'] ?>" style="vertical-align: middle" />
							<?php h($h['author']); ?>
						</td>
						<td><?php h($h['message']); ?></td>
						<td class="actions">
							<a href="?<?php h($h['commit']); ?>">view</a>
						</td>
					</tr>
					<tr class="diff">
						<td colspan="5">
							<div class="wrapper">
								<?php echo $geshi->parse_code(); ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
				</table>
			<?php else: ?>
				<p>No history</p>
			<?php endif; ?>
	</div>
</div>
<script type="text/javascript">
	$(function(){

		<?php if ($settings->show_history): ?>
		$('#history').show();
		<?php endif; ?>

		$('#history-btn').click(function(){
			$('#history').show().scrollTop();
		});

		$('tr.diff').hide();
		/**/
		$('td.commit').toggle(
			function(){
				$(this).parent().next().show();
			},
			function() {
				$(this).parent().next().hide();
			}
		);
	});
</script>