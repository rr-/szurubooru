if ($.when.all === undefined)
{
	$.when.all = function(deferreds)
	{
		var deferred = new $.Deferred();
		$.when.apply($, deferreds).then(function()
		{
			console.log(arguments);
			deferred.resolve(Array.prototype.slice.call(arguments, 0));
		}, function()
		{
			console.log(arguments);
			deferred.fail(Array.prototype.slice.call(arguments, 0));
		});

		return deferred;
	}
}
