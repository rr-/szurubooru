$.fn.hasAttr = function(name)
{
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


//modify DOM on small viewports
$(window).resize(function()
{
	if ($('body').width() == $('body').data('last-width'))
		return;
	$('#inner-content .unit').addClass('bottom-unit');
	if ($('body').width() < 600)
	{
		$('body').addClass('small-screen');
		$('#sidebar').insertAfter($('#inner-content'));
		$('#sidebar .unit').removeClass('left-unit').addClass('bottom-unit');
	}
	else
	{
		$('body').removeClass('small-screen');
		$('#sidebar').insertBefore($('#inner-content'));
		$('#sidebar .unit').removeClass('bottom-unit').addClass('left-unit');
	}
	$('body').data('last-width', $('body').width());
});
$(function()
{
	$(window).resize();
});


//autocomplete
function split(val)
{
    return val.split(/\s+/);
}

function extractLast(term)
{
    return split(term).pop();
}

$(function()
{
	var searchInput = $('#top-nav .search input');
	searchInput
		// don't navigate away from the field on tab when selecting an item
		.bind("keydown", function(event)
		{
			if (event.keyCode === $.ui.keyCode.TAB && $(this).data("autocomplete").menu.active)
			{
				event.preventDefault();
			}
		}).autocomplete({
			minLength: 0,
			source: function(request, response)
			{
				var term = extractLast(request.term);
				$.get(searchInput.attr('data-autocomplete-url') + '?json', {filter: term}, function(data)
				{
					response($.map(data.tags, function(tag) { return { label: tag, value: tag }; }));
				});
			},
			focus: function()
			{
				// prevent value inserted on focus
				return false;
			},
			select: function(event, ui)
			{
				var terms = split(this.value);
				terms.pop();
				terms.push(ui.item.value);
				terms.push('');
				this.value = terms.join(' ');
				return false;
			}
		});
});

function getTagItOptions()
{
	return {
		caseSensitive: false,
		autocomplete:
		{
			source:
				function(request, response)
				{
					var term = request.term.toLowerCase();
					var results = $.grep(this.options.availableTags, function(a)
					{
						return a.toLowerCase().indexOf(term) != -1;
					});
					if (!this.options.allowDuplicates)
						results = this._subtractArray(results, this.assignedTags());
					response(results);
				},
		}
	};
}
