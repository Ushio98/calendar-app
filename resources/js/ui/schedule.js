// 予定データの表示まわりで共通に使う関数

export const WORKLOAD_UNITS = [
  { value: '1', label: 'ページ' },
  { value: '2', label: '文字' },
];

// 和色のプリセット
export const EVENT_COLORS = [
  { value: '#3e6a8a', name: '藍' },
  { value: '#4a9aa5', name: '浅葱' },
  { value: '#6a8f4e', name: '若竹' },
  { value: '#d39a2c', name: '山吹' },
  { value: '#c8553d', name: '朱' },
  { value: '#d2869b', name: '桜' },
  { value: '#8a74a8', name: '藤' },
  { value: '#7a7068', name: '鈍' },
];

export const DEFAULT_EVENT_COLOR = EVENT_COLORS[0].value;

const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

export function formatWorkloadUnit(unit) {
  const found = WORKLOAD_UNITS.find(u => u.value === String(unit));
  return found ? found.label : '';
}

export function formatWorkload(value, unit) {
  if (value === null || value === undefined || value === '') {
    return '';
  }
  return `${value}${formatWorkloadUnit(unit)}`;
}

// 'YYYY-MM-DD' をローカル時刻の Date に変換（new Date('YYYY-MM-DD') は UTC 扱いになるため）
export function parseYmd(ymd) {
  if (!ymd) return null;
  const [y, m, d] = String(ymd).slice(0, 10).split('-').map(Number);
  return new Date(y, m - 1, d);
}

export function addDays(date, days) {
  const result = new Date(date);
  result.setDate(result.getDate() + days);
  return result;
}

export function toYmd(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

// 2026年10月29日（木）
export function formatDateLong(date) {
  return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日（${WEEKDAYS[date.getDay()]}）`;
}

// 10/29（木）
export function formatDateShort(date) {
  return `${date.getMonth() + 1}/${date.getDate()}（${WEEKDAYS[date.getDay()]}）`;
}

export function formatPeriod(startYmd, endYmd) {
  const start = parseYmd(startYmd);
  const end = parseYmd(endYmd);
  if (!start) return '';
  if (!end || toYmd(start) === toYmd(end)) {
    return formatDateShort(start);
  }
  return `${formatDateShort(start)} 〜 ${formatDateShort(end)}`;
}

// 背景色に対して読みやすい文字色（白 or 墨）を返す
export function readableTextColor(hex) {
  const match = /^#?([0-9a-f]{6})$/i.exec(hex || '');
  if (!match) return '#ffffff';
  const n = parseInt(match[1], 16);
  const channel = c => {
    const s = c / 255;
    return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
  };
  const luminance = 0.2126 * channel(n >> 16) + 0.7152 * channel((n >> 8) & 255) + 0.0722 * channel(n & 255);
  return luminance > 0.23 ? '#2f2a25' : '#ffffff';
}

// 作業量の設定方法
export const WORKLOAD_MODES = [
  { value: 'none', label: 'なし' },
  { value: 'fixed', label: '毎日同じ' },
  { value: 'daily', label: '日ごとに設定' },
];

// 開始日〜終了日の 'YYYY-MM-DD' の一覧
export function datesBetween(startYmd, endYmd) {
  const start = parseYmd(startYmd);
  const end = parseYmd(endYmd || startYmd);
  if (!start || !end || end < start) return [];
  const dates = [];
  for (const day = new Date(start); day <= end; day.setDate(day.getDate() + 1)) {
    dates.push(toYmd(day));
  }
  return dates;
}

// 予定全体の作業量の合計
export function totalWorkload(schedule) {
  if (schedule.workload_mode === 'fixed') {
    return (schedule.workload_value || 0) * datesBetween(schedule.start_date, schedule.end_date).length;
  }
  if (schedule.workload_mode === 'daily') {
    return Object.values(schedule.daily_workloads || {}).reduce((sum, value) => sum + value, 0);
  }
  return 0;
}

// 詳細画面などに出す作業量の説明
export function workloadSummary(schedule) {
  const unit = formatWorkloadUnit(schedule.workload_unit);
  const days = datesBetween(schedule.start_date, schedule.end_date).length;
  if (schedule.workload_mode === 'fixed') {
    const perDay = formatWorkload(schedule.workload_value, schedule.workload_unit);
    return days > 1 ? `毎日 ${perDay}（合計 ${totalWorkload(schedule)}${unit}）` : perDay;
  }
  if (schedule.workload_mode === 'daily') {
    return `日ごとに設定（合計 ${totalWorkload(schedule)}${unit}）`;
  }
  return '';
}

// 指定した日の予定の作業量。作業量がない日は null
export function plannedOn(schedule, date) {
  if (schedule.workload_mode === 'fixed') return schedule.workload_value;
  if (schedule.workload_mode === 'daily') {
    const value = (schedule.daily_workloads || {})[date];
    return value === undefined ? null : value;
  }
  return null;
}

// 指定した日の実績。未入力なら null
export function actualOn(schedule, date) {
  const value = (schedule.actual_workloads || {})[date];
  return value === undefined ? null : value;
}

// 実績の合計
export function totalActual(schedule) {
  return Object.values(schedule.actual_workloads || {}).reduce((sum, value) => sum + value, 0);
}

// 実績を入れた日の、予定との差の合計。実績が1日もなければ null
export function totalDifference(schedule) {
  const dates = Object.keys(schedule.actual_workloads || {});
  if (!dates.length) return null;
  return dates.reduce((sum, date) => sum + actualOn(schedule, date) - (plannedOn(schedule, date) || 0), 0);
}

// 予定との差の表示。+100 / −500 / ±0
export function formatDifference(difference) {
  if (difference === null || difference === undefined) return '';
  if (difference === 0) return '±0';
  return difference > 0 ? `+${difference}` : `−${-difference}`;
}

// 予定との差の色分けに使うクラス
export function differenceClass(difference) {
  if (difference < 0) return 'is-under';
  return difference > 0 ? 'is-over' : 'is-even';
}

