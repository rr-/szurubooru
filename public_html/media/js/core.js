if ($.when.all === undefined)
{
	$.when.all = function(deferreds)
	{
		var deferred = new $.Deferred();
		$.when.apply($, deferreds).then(function()
		{
			deferred.resolve(Array.prototype.slice.call(arguments, 0));
		}, function()
		{
			deferred.fail(Array.prototype.slice.call(arguments, 0));
		});

		return deferred;
	}
}

$(function()
{
	$('.safety a').click(function(e)
	{
		e.preventDefault();

		var aDom = $(this);
		if (aDom.hasClass('inactive'))
			return;
		aDom.addClass('inactive');

		var url = $(this).attr('href') + '?json';
		$.get(url, function(data)
		{
			if (data['success'])
			{
				window.location.reload();
			}
			else
			{
				alert(data['errorMessage']);
			}
		});
	});
});
