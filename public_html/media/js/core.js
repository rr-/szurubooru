function setCookie(name, value, exdays)
{
	var exdate = new Date();
	exdate.setDate(exdate.getDate() + exdays);
	value = escape(value) + '; path=/' + ((exdays == null) ? '' : '; expires=' + exdate.toUTCString());
	document.cookie = name + '=' + value;
}

function getCookie(name)
{
	var value = document.cookie;
	var start = value.indexOf(' ' + name + '=');

	if (start == -1)
		start = value.indexOf(name + '=');

	if (start == -1)
		return null;

	start = value.indexOf('=', start) + 1;
	var end = value.indexOf(';', start);
	if (end == -1)
		end = value.length;

	return unescape(value.substring(start, end));
}

function rememberLastSearchQuery()
{
	//lastSearchQuery variable is obtained from layout
	setCookie('last-search-query', lastSearchQuery);
}

//core functionalities, prototypes
$.fn.hasAttr = function(name)
{
	return this.attr(name) !== undefined;
};

$.fn.bindOnce = function(name, eventName, callback)
{
	$.each(this, function(i, item)
	{
		if ($(item).data(name) == name)
			return;
		$(item).data(name, name);
		$(item).on(eventName, callback);
	});
};



//safety trigger
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
		$.get(url).always(function(data)
		{
			if (data['success'])
				window.location.reload();
			else
			{
				alert(data['message'] ? data['message'] : 'Fatal error');
				aDom.removeClass('inactive');
			}
		});
	});
});



//basic event listeners
$(function()
{
	$('body').bind('dom-update', function()
	{
		//event confirmations
		function confirmEvent(e)
		{
			if (!confirm($(this).attr('data-confirm-text')))
			{
				e.preventDefault();
				e.stopPropagation();
			}
		}

		$('form.confirmable').bindOnce('confirmation', 'submit', confirmEvent);
		$('a.confirmable').bindOnce('confirmation', 'click', confirmEvent);


		//simple action buttons
		$('a.simple-action').bindOnce('simple-action', 'click', function(e)
		{
			if (e.isPropagationStopped())
				return;

			e.preventDefault();
			rememberLastSearchQuery();

			var aDom = $(this);
			if (aDom.hasClass('inactive'))
				return;
			aDom.addClass('inactive');

			var url = $(this).attr('href') + '?json';
			$.get(url, {submit: 1}).always(function(data)
			{
				if (data['success'])
				{
					if (aDom.hasAttr('data-redirect-url'))
						window.location.href = aDom.attr('data-redirect-url');
					else if (aDom.data('callback'))
						aDom.data('callback')();
					else
						window.location.reload();
				}
				else
				{
					alert(data['message'] ? data['message'] : 'Fatal error');
					aDom.removeClass('inactive');
				}
			});
		});


		//attach data from submit buttons to forms before .submit() gets called
		$('.submit').each(function()
		{
			$(this).bindOnce('submit-faux-input', 'click', function()
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


	//try to remember last search query
	window.onbeforeunload = rememberLastSearchQuery;
});



//modify DOM on small viewports
function processSidebar()
{
	if ($('#small-screen').is(':visible'))
		$('#sidebar').insertAfter($('#inner-content'));
	else
		$('#sidebar').insertBefore($('#inner-content'));
}
$(function()
{
	$(window).resize(function()
	{
		fixSize();
		if ($('body').width() == $('body').data('last-width'))
			return;
		$('body').data('last-width', $('body').width());
		$('body').trigger('dom-update');
	});
	$('body').bind('dom-update', processSidebar);
	fixSize();
});

var fixedEvenOnce = false;
function fixSize()
{
	if ($('#small-screen').is(':visible'))
		return;
	var multiply = 168;
	var oldWidth = $('.main-wrapper:eq(0)').width();
	$('.main-wrapper:eq(0)').width('');
	var newWidth = $('.main-wrapper:eq(0)').width();
	if (oldWidth != newWidth || !fixedEvenOnce)
	{
		$('.main-wrapper').width(multiply * Math.floor(newWidth / multiply));
		fixedEvenOnce = true;
	}
}



//autocomplete
function split(val)
{
    return val.split(/\s+/);
}

function retrieveTags(searchTerm, cb)
{
	var options = { search: searchTerm };
	$.getJSON('/tags-autocomplete?json', options, function(data)
	{
		var tags = $.map(data.tags.slice(0, 15), function(tag)
		{
			var ret =
			{
				label: tag.name + ' (' + tag.count + ')',
				value: tag.name,
			};
			return ret;
		});

		cb(tags);
	});
}

$(function()
{
	$('.autocomplete').each(function()
	{
		var options =
		{
			minLength: 1,
			source: function(request, response)
			{
				var terms = split(request.term);
				var term = terms.pop();
				if (term != '')
					retrieveTags(term, response);
			},
			focus: function(e)
			{
				// prevent value inserted on focus
				e.preventDefault();
			},
			select: function(e, ui)
			{
				e.preventDefault();
				var terms = split(this.value);
				terms.pop();
				terms.push(ui.item.value);
				terms.push('');
				this.value = terms.join(' ');
			}
		};

		if ($(this).parents('#top-nav').length != 0)
		{
			options['position'] =
			{
				my: 'right top',
				at: 'right bottom'
			};
		}

		var searchInput = $(this);
		searchInput
		// don't navigate away from the field on tab when selecting an item
		.bind('keydown', function(e)
		{
			if (e.keyCode === $.ui.keyCode.TAB && $(this).data('autocomplete').menu.active)
				e.preventDefault();
		}).autocomplete(options);
	});
});

function attachTagIt(target)
{
	var tagItOptions =
	{
		caseSensitive: false,
		onTagClicked: function(e, ui)
		{
			var targetTagit = ui.tag.parents('.tagit');
			var context = target.tagit('assignedTags');
			options = { context: context, tag: ui.tagLabel };
			if (targetTagit.siblings('.related-tags:eq(0)').data('for') == options.tag)
			{
				targetTagit.siblings('.related-tags').slideUp(function()
				{
					$(this).remove();
				});
				return;
			}

			$.getJSON('/tags-related?json', options, function(data)
			{
				var list = $('<ul>');
				$.each(data.tags, function(i, tag)
				{
					var link = $('<a>');
					link.attr('href', '/posts/' + tag.name + '/');
					link.text('#' + tag.name);
					link.click(function(e)
					{
						e.preventDefault();
						target.tagit('createTag', tag.name);
					});
					list.append(link.wrap('<li/>').parent());
				});
				targetTagit.siblings('.related-tags').slideUp(function()
				{
					$(this).remove();
				});
				var div = $('<div>');
				div.data('for', options.tag);
				div.addClass('related-tags');
				div.append('<p>Related tags:</p>');
				div.append(list);
				div.append('<div class="clear"></div>');
				div.insertAfter(targetTagit).hide().slideDown();
			});
		},

		autocomplete:
		{
			source:
				function(request, response)
				{
					var tagit = this;
					//var context = tagit.element.tagit('assignedTags');
					retrieveTags(request.term.toLowerCase(), function(tags)
					{
						if (!tagit.options.allowDuplicates)
						{
							tags = $.grep(tags, function(tag)
							{
								return tagit.assignedTags().indexOf(tag.value) == -1;
							});
						}
						response(tags);
					});
				},
		}
	};

	tagItOptions.placeholderText = target.attr('placeholder');
	target.tagit(tagItOptions);
}



//prevent keybindings from executing when flash posts are focused
var oldMousetrapBind = Mousetrap.bind;
Mousetrap.bind = function(key, func, args)
{
	oldMousetrapBind(key, function()
	{
		if ($(document.activeElement).parents('.post-type-flash').length > 0)
			return false;

		func();
	}, args);
};



//hotkeys
$(function()
{
	Mousetrap.bind('q', function()
		{
			$('#top-nav input').focus();
			return false;
		}, 'keyup');

	Mousetrap.bind('w', function()
		{
			$('body,html').animate({scrollTop: '-=150px'}, 200);
		});

	Mousetrap.bind('s', function()
		{
			$('body,html').animate({scrollTop: '+=150px'}, 200);
		});

	Mousetrap.bind('a', function()
		{
			var url = $('.paginator:visible .prev:not(.disabled) a').attr('href');
			if (typeof url !== 'undefined')
				window.location.href = url;
		}, 'keyup');

	Mousetrap.bind('d', function()
		{
			var url = $('.paginator:visible .next:not(.disabled) a').attr('href');
			if (typeof url !== 'undefined')
				window.location.href = url;
		}, 'keyup');

	Mousetrap.bind('p', function()
		{
			$('.post a').eq(0).focus();
			return false;
		}, 'keyup');
});



function enableExitConfirmation()
{
	$(window).bind('beforeunload', function(e)
	{
		return 'There are unsaved changes.';
	});
}

function disableExitConfirmation()
{
	$(window).unbind('beforeunload');
}
