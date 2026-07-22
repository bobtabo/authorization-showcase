#!/usr/bin/env bash
#
# Stop: タスク完了時にデスクトップ通知 + サウンドで知らせる。
# 通知手段は環境ごとに異なるため、使えるものを順に試す（全て無ければ静かに終了）。
# 常に exit 0（完了を妨げない）。

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib.sh
. "$DIR/lib.sh"

TITLE="Claude Code"
MSG="タスクが完了しました (authorization-showcase)"

# ---- デスクトップ通知 ----
if have notify-send; then
  notify-send "$TITLE" "$MSG" >/dev/null 2>&1 || true
elif have osascript; then   # macOS
  osascript -e "display notification \"$MSG\" with title \"$TITLE\"" >/dev/null 2>&1 || true
elif have powershell.exe; then  # WSL/Windows
  powershell.exe -NoProfile -Command "[void][System.Reflection.Assembly]::LoadWithPartialName('System.Windows.Forms'); [System.Windows.Forms.MessageBox]::Show('$MSG','$TITLE')" >/dev/null 2>&1 || true
fi

# ---- サウンド ----
if have paplay && [ -f /usr/share/sounds/freedesktop/stereo/complete.oga ]; then
  paplay /usr/share/sounds/freedesktop/stereo/complete.oga >/dev/null 2>&1 || true
elif have afplay; then       # macOS
  afplay /System/Library/Sounds/Glass.aiff >/dev/null 2>&1 || true
else
  printf '\a' >&2            # ターミナルベル(フォールバック)
fi

exit 0
