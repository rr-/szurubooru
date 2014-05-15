$(function()
{
	$('body').bind('dom-update', function()
	{
		$('.post a.toggle-tag').bindOnce('toggle-tag', 'click', function(e)
		{
			e.preventDefault();

			var aDom = $(this);
			if (aDom.hasClass('inactive'))
				return;
			aDom.addClass('inactive');

			var enable = !aDom.parents('.post').hasClass('tagged');
			var url = $(this).attr('href');
			url = url.replace('_enable_', enable ? '1' : '0');
			postJSON({ url: url }).success(function(data)
			{
				aDom.removeClass('inactive');
				aDom.parents('.post').removeClass('tagged');
				if (enable)
					aDom.parents('.post').addClass('tagged');
				aDom.text(enable
					? aDom.attr('data-text-tagged')
					: aDom.attr('data-text-untagged'));
			}).error(function(xhr)
			{
				alert(xhr.responseJSON
					? xhr.responseJSON.message
					: 'Fatal error');
				aDom.removeClass('inactive');
			});
		});
	});
});
