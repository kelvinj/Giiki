<?php
include 'giiki/theme/_header.php';
?>

    <div id="wrapper" class="wat-cf">
		<div id="main">
			<!-- messages //-->
			<?php app_show_feedback(); ?>

			<div class="block" id="main-content">
				<div class="secondary-navigation">
					<ul class="wat-cf">
						<li class="first active"><a href="<?php h(http_request_uri()); ?>"><?php h($g->get_page_name(true)) ?></a></li>
						<?php if (defined('COMMIT')): ?>
						<li><a href="?view">show current</a></li>
						<?php else: ?>
						<li><a href="?edit">edit</a></li>
						<?php endif; ?>
					</ul>
				</div>
				<div class="content" id="page-content">
					<div class="inner">
						<?php app_output($g->get_page_contents()); ?>
					</div>
				</div>
			</div>

			<?php include 'giiki/theme/_history.php'; ?>


		</div>

		<div id="sidebar">
			<?php include 'giiki/theme/_sidebar.php'; ?>

<script type="text/javascript" charset="utf-8">
// <![CDATA[
    $(document).ready(function() {
        $.localScroll({
			lazy: true
		});

		$('#pagename').focus(function(){this.select();});
		$('#search').focus();

		$('a[href^=http]').each(function(){
			$(this).attr('target', '_blank').after('<img alt="Save" class="noPrint" src="/giiki/theme/assets/external.png" />');
		});
    });
// ]]>
</script>

<?php include 'giiki/theme/_footer.php'; ?>