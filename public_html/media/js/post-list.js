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

			var url = $(this).attr('href') + '?json';
			$.get(url, {submit: 1}).always(function(data)
			{
				if (data['success'])
				{
					aDom.removeClass('inactive');
					aDom.parents('.post').toggleClass('tagged');
					aDom.text(aDom.parents('.post').hasClass('tagged')
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
	$('body').trigger('dom-update');
});
