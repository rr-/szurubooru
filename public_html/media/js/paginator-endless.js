function scrolled()
{
	var margin = 150;
	var target = $('.paginator-content:eq(0)');
	var y = $(window).scrollTop() + $(window).height();
	var maxY = target.height() + target.position().top;
	if (y >= maxY - margin)
	{
		var pageNext = $(document).data('page-next');
		var pageDone = $(document).data('page-done');
		if (pageNext == null)
		{
			pageNext = $('.paginator .next:not(.disabled) a').attr('href');
		}
		if (pageNext != null && pageNext != pageDone)
		{
			$(document).data('page-done', pageNext);
			getHtml(pageNext).success(function(response)
			{
				var dom = $(response);
				var nextPage = dom.find('.paginator .next:not(.disabled) a').attr('href');
				$(document).data('page-next', nextPage);

				var source = $(response).find('.paginator-content');
				target.append(source
					.children()
					.css({opacity: 0})
					.animate({opacity: 1}, 'slow'));

				$('body').trigger('dom-update');
				scrolled();
			});
		}
	}
}

$(function()
{
	$('.paginator').hide();
	$(window).scroll(scrolled);
	scrolled();
});
