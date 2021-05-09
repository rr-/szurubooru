#!/usr/bin/env bash

set -euo pipefail

npm run watchify &

c1="";
while :; do
    c2=$(find html css img -type f -and -not -iname '*autogen*'|sort|xargs cat|md5sum);
    [[ $c1 != $c2 ]] && npm run build -- --debug --no-js --no-binary-assets --no-web-app-files
    c1=$c2;
    sleep 1;
done

wait
