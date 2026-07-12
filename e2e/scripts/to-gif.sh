#!/bin/bash
set -euo pipefail

INPUT_DIR="$(cd "$(dirname "$0")/.." && pwd)/output"
OUTPUT_DIR="$(cd "$(dirname "$0")/../.." && pwd)/docs/gif"

if [ ! -d "${INPUT_DIR}" ]; then
  echo "⚠️  ${INPUT_DIR} が存在しません。先に npm run record を実行してください。" >&2
  exit 1
fi

mkdir -p "${OUTPUT_DIR}"

find "${INPUT_DIR}" -name "*.webm" | while read -r webm; do
  name=$(basename "${webm}" .webm)
  ffmpeg -nostdin -i "${webm}" \
    -vf "fps=15,scale=960:-1:flags=lanczos,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse" \
    -loop 0 \
    "${OUTPUT_DIR}/${name}.gif" -y
  echo "✅ ${name}.gif を生成しました"
done
