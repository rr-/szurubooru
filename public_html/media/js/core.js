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
