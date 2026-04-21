// 日付・日時の表示用フォーマッタ。サーバー側の ISO 8601 文字列や Y-m-d をそのまま
// 画面に出すと `2026-04-21T00:00:00.000000Z` のような見づらい表現になるため、
// 受講者にとって読みやすい日本語表記へ揃える。

export function formatDate(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}
