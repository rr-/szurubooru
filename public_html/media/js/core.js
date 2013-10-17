$.fn.hasAttr = function(name) {
	return this.attr(name) !== undefined;
};

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

	function confirmEvent(e)
	{
		if (!confirm($(this).attr('data-confirm-text')))
		{
			e.preventDefault();
			e.stopPropagation();
		}
	}

	$('form[data-confirm-text]').submit(confirmEvent);
	$('a[data-confirm-text]').click(confirmEvent);

	$('a.simple-action').click(function(e)
	{
		if(e.isPropagationStopped())
			return;

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
				if (aDom.hasAttr('data-redirect-url'))
					window.location.href = aDom.attr('data-redirect-url');
				else
					window.location.reload();
			}
			else
			{
				alert(data['errorMessage']);
				aDom.removeClass('inactive');
			}
		});
	});


	//attach data from submit buttons to forms before .submit() gets called
	$(':submit').each(function()
	{
		$(this).click(function()
		{
			var form = $(this).closest('form');
			form.find('.faux-submit').remove();
			var input = $('<input class="faux-submit" type="hidden"/>').attr({
				name: $(this).attr('name'),
				value: $(this).val()
			});
			form.append(input);
		});
	});
});
