export function understandingBarClass(level: number): string {
  if (level <= 2) return 'bg-red-400';
  if (level === 3) return 'bg-yellow-400';
  return 'bg-emerald-400';
}
