$(function()
{
	$('body').bind('dom-update', function()
	{
		$('.post a.toggle-tag').click(function(e)
		{
			if(e.isPropagationStopped())
				return;

			e.preventDefault();
			e.stopPropagation();

			var aDom = $(this);
			if (aDom.hasClass('inactive'))
				return;
			aDom.addClass('inactive');

			var enable = !aDom.parents('.post').hasClass('tagged');
			var url = $(this).attr('href') + '?json';
			url = url.replace('_enable_', enable ? '1' : '0');
			$.get(url, {submit: 1}).always(function(data)
			{
				if (data['success'])
				{
					aDom.removeClass('inactive');
					aDom.parents('.post').removeClass('tagged');
					if (enable)
						aDom.parents('.post').addClass('tagged');
					aDom.text(enable
						? aDom.attr('data-text-tagged')
						: aDom.attr('data-text-untagged'));
				}
				else
				{
					alert(data['message'] ? data['message'] : 'Fatal error');
					aDom.removeClass('inactive');
				}
			});
		});
	});
});
