#!/usr/bin/env bash
# Usage: scripts/run-server-tests.sh [path]
# - path: optional dir/file/node (default: szurubooru/, example, szurubooru/tests/api/test_info.py)

set -euo pipefail

# Build the testing image (with xdist)
docker buildx build --load --target testing -t szuru-server-tests ./server

# Optional path filter (dir/file/node), default to full suite.
TARGET="${1:-szurubooru/}"

run_tests() {
  local output_file
  output_file=$(mktemp)
  if docker run --rm -t szuru-server-tests "$@" 2>&1 | tee "$output_file"; then
    rm -f "$output_file"
    return 0
  fi

  local status=${PIPESTATUS[0]}
  echo "docker run failed with exit code ${status}."
  echo "----- docker run output -----"
  cat "$output_file"
  echo "-----------------------------"
  if grep -qE "no tests ran|collected 0 items" "$output_file"; then
    echo "No tests collected for target '${TARGET}'."
    echo "Check the path (tests live under szurubooru/ in the container, so an example path looks like \`szurubooru/tests/api/test_info.py\`)."
  fi
  rm -f "$output_file"
  return "$status"
}

# Run in parallel.
run_tests -n auto --dist=loadfile --color=yes "$TARGET"
