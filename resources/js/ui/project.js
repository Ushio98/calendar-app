import { datesBetween, formatWorkloadUnit, parseYmd, plannedOn, toYmd, totalActual, totalWorkload } from './schedule';

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

// プロジェクトの進み具合を単位ごとに集計する（「ページ」と「文字」は合算できないため）
// 作業量のない予定は含めない
export function projectProgress(project) {
  const today = toYmd(new Date());
  const groups = new Map();

  (project.schedules || [])
    .filter(schedule => schedule.workload_mode && schedule.workload_mode !== 'none')
    .forEach(schedule => {
      const key = String(schedule.workload_unit || '');
      if (!groups.has(key)) {
        groups.set(key, { unit: formatWorkloadUnit(key), planned: 0, plannedToDate: 0, actual: 0 });
      }
      const group = groups.get(key);
      group.planned += totalWorkload(schedule);
      group.actual += totalActual(schedule);
      group.plannedToDate += datesBetween(schedule.start_date, schedule.end_date)
        .filter(date => date <= today)
        .reduce((sum, date) => sum + (plannedOn(schedule, date) || 0), 0);
    });

  return [...groups.values()].map(group => ({
    ...group,
    key: group.unit,
    percent: group.planned > 0 ? Math.round((group.actual / group.planned) * 100) : null,
    // バー上の「今日までの予定」の位置
    paceMark: group.planned > 0 ? Math.min((group.plannedToDate / group.planned) * 100, 100) : null,
    difference: group.actual - group.plannedToDate,
    started: group.plannedToDate > 0 || group.actual > 0,
  }));
}

// 進行中のプロジェクトかどうか。予定の合間の日も進行中として扱う
export function isProjectActive(project, today = toYmd(new Date())) {
  const schedules = project.schedules || [];
  if (!schedules.length) return false;

  const firstStart = schedules.map(s => s.start_date).reduce((a, b) => (a < b ? a : b));
  const lastEnd = schedules.map(s => s.end_date || s.start_date).reduce((a, b) => (a > b ? a : b));

  if (firstStart > today) return false;
  return lastEnd >= today || (!!project.milestone_date && project.milestone_date >= today);
}

// 進行中のプロジェクトを、日付が近い順（日付なしは後ろ）に並べる
export function activeProjects(projects) {
  return projects
    .filter(project => isProjectActive(project))
    .sort((a, b) => {
      if (a.milestone_date && b.milestone_date) return a.milestone_date.localeCompare(b.milestone_date);
      if (a.milestone_date || b.milestone_date) return a.milestone_date ? -1 : 1;
      return a.name.localeCompare(b.name, 'ja');
    });
}

