import { parseYmd, toYmd } from './schedule';

// プロジェクトに設定できる日付の種類
export const MILESTONE_TYPES = [
  { value: 'delivery', label: '納品日' },
  { value: 'release', label: 'リリース日' },
  { value: 'sale', label: '販売日' },
  { value: 'event', label: 'イベント日' },
];

export function milestoneLabel(type) {
  const found = MILESTONE_TYPES.find(item => item.value === type);
  return found ? found.label : '';
}

// 今日からの日数（当日は 0、過ぎていれば負の数）
function daysUntil(ymd) {
  const today = parseYmd(toYmd(new Date()));
  return Math.round((parseYmd(ymd) - today) / 86400000);
}

// あと3日／本日／2日前
export function milestoneCountdown(ymd) {
  if (!ymd) return '';
  const days = daysUntil(ymd);
  if (days === 0) return '本日';
  return days > 0 ? `あと${days}日` : `${-days}日前`;
}

export function isMilestonePast(ymd) {
  return !!ymd && daysUntil(ymd) < 0;
}
