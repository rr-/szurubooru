function scrolled()
{
	var margin = 150;
	if ($(document).height() <= $(window).scrollTop() + $(window).height() + margin)
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
			$.get(pageNext, [], function(response)
			{
				var dom = $(response);
				var nextPage = dom.find('.paginator .next:not(.disabled) a').attr('href');
				$(document).data('page-next', nextPage);
				$('.paginator-content').append($(response).find('.paginator-content').children().css({opacity: 0}).animate({opacity: 1}, 'slow'));
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
