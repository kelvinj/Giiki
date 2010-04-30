<?php
$current_user = $g->get_logged_in_user();
$settings = $current_user->settings();
if (http_is_post()) {
    if ($_POST['form_action'] == 'savepage') {
		$g->set_page_contents($_POST['contents']);
        Fu_Feedback::set_flash('page saved');

        if ($settings->remain_edit_mode) {
            http_redirect(http_request_uri());
        }
        else {
            http_redirect('/'.$g->get_page());
        }
    }
    if ($_POST['form_action'] == 'delpage') {
		if ($g->delete_page()) {
            Fu_Feedback::set_flash('page deleted');
            http_redirect('/');
        }

    }
}

include 'giiki/theme/_header.php';
$editor = $settings->editor;
?>

    <div id="wrapper" class="wat-cf">
		<div id="main">
            <!-- messages //-->
			<?php app_show_feedback(); ?>

			<div class="block" id="main-content">
				<div class="secondary-navigation">
					<ul class="wat-cf">
						<li class="first"><a href="?"><?php h($g->get_page_name()) ?></a></li>
						<li class="last active"><a href="?edit">edit</a></li>
					</ul>
				</div>
				<div class="content">
					<div class="inner">
                        <form action="" method="post" id="edit_form">
                            <p>
                                <textarea name="contents" id="contents"><?php h($g->get_page_contents()); ?></textarea>
                            </p>
                            <div class="group navform wat-cf">
                                <input type="hidden" name="form_action" value="savepage" />
                                <button class="button" type="submit">
                                    <img src="/giiki/theme/assets/tick.png" alt="Save"> Save
                                </button>
                            </div>
                        </form>

                        <?php if ($editor == 'tinymce'): ?>
                        <script type="text/javascript" src="/giiki/theme/js/tiny_mce/tiny_mce.js"></script>
                        <script type="text/javascript" src="/giiki/theme/js/tiny_mce/plugins/tinybrowser/tb_tinymce.js.php"></script>

                        <script type="text/javascript">
                        $(function(){
                            tinyMCE.init({
                                // General options
                                mode : "exact",
                                elements : "contents",
                                theme : "advanced",
                                skin : "o2k7",
                                skin_variant : "black",
                                plugins : "pagebreak,style,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,print,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups,autosave",

                                // Theme options
                                theme_advanced_buttons1 : "save,cancel,|,bold,italic,underline,strikethrough,cite,abbr,acronym,del,ins,attribs,|,hr,removeformat,|,formatselect,|,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,image,cleanup",
                                theme_advanced_buttons2 : "tablecontrols,|,visualchars,nonbreaking,pagebreak,|,sub,sup,|,charmap,advhr,|,ltr,rtl,|,fullscreen,code",
                                theme_advanced_buttons3 : "",
                                theme_advanced_buttons4 : "",
                                theme_advanced_toolbar_location : "top",
                                theme_advanced_toolbar_align : "left",
                                theme_advanced_statusbar_location : "bottom",
                                theme_advanced_resizing : true,

                                // Example content CSS (should be your site CSS)
                                content_css : "css/content.css",

                                // Drop lists for link/image/media/template dialogs
                                template_external_list_url : "lists/template_list.js",
                                external_link_list_url : "lists/link_list.js",
                                external_image_list_url : "lists/image_list.js",
                                media_external_list_url : "lists/media_list.js",

                                // Replace values for the template plugin
                                template_replace_values : {
                                    username : "Some User",
                                    staffid : "991234"
                                },

                                <?php if ($settings->fullscreen_edit): ?>
                                init_instance_callback: function (inst){
                                    if (inst.editorId != 'mce_fullscreen') {
                                        inst.execCommand('mceFullScreen');
                                    }
                                },
                                <?php endif; ?>
                                save_oncancelcallback: function () {
                                    document.location.href= "<?php echo '/'.$g->get_page() ?>";
                                },

                                setupcontent_callback: function (){
                                    var inst = tinyMCE.selectedInstance;
                                    inst.addShortcut('ctrl+shift+z', 'Cancel web developer redo', function(){});
                                },

                                relative_urls: true,
                                file_browser_callback: "tinyBrowser"
                            });

                            shortcut.add("Meta+Shift+Z",function() {}); // ignore this, bloody web developer toolbar

                        });
                        </script>
                        <?php endif; ?>
					</div>
				</div>
			</div>

            <?php if ($g->is_admin_user()): ?>
			<div class="block">
				<div class="content">
					<h2 class="title">Delete This Page</h2>
					<div class="inner">
						<!-- messages //-->
						<?php
						if ($_POST['form_action'] == 'delpage') {
							app_show_feedback();
						}
						?>
						<form action="" method="post" class="form">
						<div class="group navform wat-cf">
							<input type="hidden" name="form_action" value="delpage" />
							<button type="submit" class="button">
                                <img alt="Delete" src="/giiki/theme/assets/cross.png"> Delete
                            </button>
						</div>
						</form>
					</div>
				</div>
			</div>
            <?php endif; ?>

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

<script type="text/javascript" charset="utf-8" src="/giiki/theme/js/shortcut.js"></script>
<?php include 'giiki/theme/_footer.php'; ?>