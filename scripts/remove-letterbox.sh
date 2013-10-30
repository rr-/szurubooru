#!/bin/sh

process () {
	x="$1";
	echo "$x";
	convert "$x" -fuzz 5% -trim +repage tmp && mv tmp "$x"
}

while read x; do
	process "$x";
done
for x in $@; do
	process "$x";
done
