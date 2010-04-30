			<div id="footer">
				<div class="block">
					<p>Copyright © <?php echo date('Y') ?> Gi’iki.</p>
				</div>
			</div>
		</div>


	</div>

</div>


<script type="text/javascript">
	$(function(){
		$('.flash').css('cursor', 'pointer').click(function(){
			$(this).fadeOut();
		});

		var ft = 0;
		$('#page-content').append('<h4 class="printOnly">Footnotes</h4><ol class="footnotes printOnly" />').find('.inner a').each(function(i){
			var t = $(this).text();
			if (t.substring(0, 4) == 'http') return;

			ft++;
			$(this).after('<sup class="printOnly">'+ft+'</sup>');
			$('<li>'+$(this)[0].href+'</li>').appendTo('.footnotes');
		});

	});

</script>
<script type="text/javascript" charset="utf-8" src="/giiki/theme/js/jquery.localscroll.js"></script>
<script type="text/javascript" charset="utf-8" src="/giiki/theme/js/jquery.scrollto.js"></script>

</body>
</html>