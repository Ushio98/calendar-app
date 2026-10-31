<template>
  <section class="page">
    <div class="page-header">
      <div>
        <h2 class="page-title">カレンダー</h2>
        <p class="page-subtitle">日付をクリックすると、その日の予定を追加できます。</p>
      </div>
      <div class="page-header__actions">
        <button type="button" class="btn btn--ghost" @click="goToday">今日</button>
        <button type="button" class="btn btn--primary" @click="openCreate()">
          <AppIcon name="plus" :size="18" />予定を追加
        </button>
      </div>
    </div>

    <div class="card calendar-card" :class="{ 'is-loading': loading }">
      <VCalendar
        ref="calendar"
        class="tsumugu-calendar"
        :first-day-of-week="2"
        locale="ja"
        :masks="{ title: 'YYYY年 M月' }"
        expanded
        borderless
        transparent
      >
        <template #day-content="{ day }">
          <div
            class="day-cell"
            :class="{
              'is-today': day.isToday,
              'is-outside': !day.inMonth,
              'is-sunday': day.weekday === 1,
              'is-saturday': day.weekday === 7,
            }"
            @click="openCreate(day.id)"
          >
            <span class="day-cell__number">{{ day.day }}</span>
            <ul class="day-cell__events">
              <li v-for="(segment, lane) in layout[day.id] || []" :key="segment ? segment.schedule.id : `spacer-${lane}`">
                <button
                  v-if="segment && segment.schedule.project"
                  type="button"
                  class="event-chip milestone-chip"
                  :title="`${milestoneLabel(segment.schedule.project.milestone_type)}：${segment.schedule.title}`"
                  :aria-label="`${milestoneLabel(segment.schedule.project.milestone_type)}：${segment.schedule.title}`"
                  @click.stop="selectedProject = segment.schedule.project"
                >
                  <span class="event-chip__label">
                    <AppIcon name="flag" :size="11" class="milestone-chip__icon" />{{ segment.schedule.title }}
                    <small>{{ milestoneLabel(segment.schedule.project.milestone_type) }}</small>
                  </span>
                </button>
                <button
                  v-else-if="segment"
                  type="button"
                  class="event-chip"
                  :class="{
                    'is-cont-left': !segment.isStart,
                    'is-cont-right': !segment.isEnd,
                    'is-join-left': !segment.isStart && !segment.isRowStart,
                    'is-join-right': !segment.isEnd && !segment.isRowEnd,
                    'is-hovered': hoveredId === segment.schedule.id,
                  }"
                  :style="chipStyle(segment.schedule.color)"
                  :title="segment.schedule.title"
                  :aria-label="segment.schedule.title"
                  @click.stop="openDetails(segment.schedule)"
                  @mouseenter="hoveredId = segment.schedule.id"
                  @mouseleave="hoveredId = null"
                >
                  <span v-if="segment.showTitle" class="event-chip__label" :style="{ '--span': segment.span }">
                    {{ segment.schedule.title }}
                  </span>
                </button>
                <span v-else class="event-chip event-chip--spacer" aria-hidden="true"></span>
              </li>
            </ul>
          </div>
        </template>
      </VCalendar>
    </div>

    <!-- 追加・編集 -->
    <BaseModal
      :open="formOpen"
      :title="editingId ? '予定を編集' : '予定を追加'"
      :closable="!submitting"
      @close="closeForm"
    >
      <EventForm
        v-if="formOpen"
        :key="formKey"
        :initial="formInitial"
        :submitting="submitting"
        :server-errors="serverErrors"
        :projects="projects"
        :submit-label="editingId ? '変更を保存' : '追加する'"
        @project-created="onProjectCreated"
        @submit="saveSchedule"
        @cancel="closeForm"
      />
    </BaseModal>

    <!-- 詳細 -->
    <BaseModal
      :open="!!selected"
      :title="selected ? selected.title : ''"
      :accent-color="selected ? selected.color : ''"
      @close="selected = null"
    >
      <dl v-if="selected" class="detail-list">
        <div class="detail-list__row">
          <dt><AppIcon name="calendar" :size="18" /><span class="visually-hidden">期間</span></dt>
          <dd>{{ formatPeriod(selected.start_date, selected.end_date) }}</dd>
        </div>
        <div v-if="projectOf(selected)" class="detail-list__row">
          <dt><AppIcon name="folder" :size="18" /><span class="visually-hidden">プロジェクト</span></dt>
          <dd>{{ projectOf(selected).name }}</dd>
        </div>
        <div v-if="workloadSummary(selected)" class="detail-list__row">
          <dt><AppIcon name="layers" :size="18" /><span class="visually-hidden">作業量</span></dt>
          <dd>
            {{ workloadSummary(selected) }}
            <div class="progress-table-wrap">
              <table class="progress-table">
                <thead>
                  <tr>
                    <th scope="col">日付</th>
                    <th scope="col" class="is-number">予定</th>
                    <th scope="col" class="is-number">実績</th>
                    <th scope="col" class="is-number">差</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="date in datesBetween(selected.start_date, selected.end_date)"
                    :key="date"
                    :class="{ 'is-today': date === todayYmd }"
                  >
                    <th scope="row">{{ formatDateShort(parseYmd(date)) }}</th>
                    <td class="is-number">{{ plannedOn(selected, date) ?? '—' }}</td>
                    <td class="is-number">
                      <ActualInput
                        v-if="date <= todayYmd"
                        :schedule-id="selected.id"
                        :date="date"
                        :value="actualOn(selected, date)"
                        :label="`${formatDateShort(parseYmd(date))}の実績`"
:on-saved="actualSaver(selected, date)"
                      />
                      <span v-else class="progress-table__future" title="未来の日付には入力できません">—</span>
                    </td>
                    <td class="is-number">
                      <span
                        v-if="actualOn(selected, date) !== null"
                        class="progress-diff"
                        :class="differenceClass(actualOn(selected, date) - (plannedOn(selected, date) || 0))"
                      >{{ formatDifference(actualOn(selected, date) - (plannedOn(selected, date) || 0)) }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="progress-summary">
              実績の合計 <strong>{{ totalActual(selected) }}</strong>{{ formatWorkloadUnit(selected.workload_unit) }}
              <span
                v-if="totalDifference(selected) !== null"
                class="progress-diff"
                :class="differenceClass(totalDifference(selected))"
              >入力した日の予定との差 {{ formatDifference(totalDifference(selected)) }}</span>
            </p>
          </dd>
        </div>
        <div v-if="selected.memo" class="detail-list__row">
          <dt><AppIcon name="note" :size="18" /><span class="visually-hidden">メモ</span></dt>
          <dd class="detail-list__memo">{{ selected.memo }}</dd>
        </div>
      </dl>
      <template #footer>
        <button type="button" class="btn btn--danger-ghost modal__footer-start" :disabled="deleting" @click="deleteSchedule">
          <AppIcon name="trash" :size="18" />削除
        </button>
        <button type="button" class="btn btn--primary" @click="openEdit">
          <AppIcon name="edit" :size="18" />編集
        </button>
      </template>
    </BaseModal>

    <!-- プロジェクトの日付 -->
    <BaseModal :open="!!selectedProject" :title="selectedProject ? selectedProject.name : ''" size="sm" @close="selectedProject = null">
      <p v-if="selectedProject" class="project-milestone" :class="{ 'is-past': isMilestonePast(selectedProject.milestone_date) }">
        <AppIcon name="flag" :size="16" />
        <span class="project-milestone__type">{{ milestoneLabel(selectedProject.milestone_type) }}</span>
        <span>{{ formatDateLong(parseYmd(selectedProject.milestone_date)) }}</span>
        <span class="project-milestone__countdown">{{ milestoneCountdown(selectedProject.milestone_date) }}</span>
      </p>
      <template #footer>
        <router-link to="/projects" class="btn btn--primary">
          <AppIcon name="folder" :size="18" />プロジェクトを開く
        </router-link>
      </template>
    </BaseModal>
  </section>
</template>

<script>
import { Calendar } from 'v-calendar';
import axios from 'axios';
import ActualInput from './ActualInput.vue';
import AppIcon from './ui/AppIcon.vue';
import BaseModal from './ui/BaseModal.vue';
import EventForm from './EventForm.vue';
import { toast } from '../ui/toast';
import { confirmDialog } from '../ui/confirm';
import { isMilestonePast, milestoneCountdown, milestoneLabel } from '../ui/project';
import {
  actualOn,
  addDays,
  datesBetween,
  DEFAULT_EVENT_COLOR,
  differenceClass,
  formatDateLong,
  formatDateShort,
  formatDifference,
  formatPeriod,
  formatWorkload,
  formatWorkloadUnit,
  parseYmd,
  plannedOn,
  readableTextColor,
  toYmd,
  totalActual,
  totalDifference,
  workloadSummary,
} from '../ui/schedule';

export default {
  name: 'Events',
  components: {
    VCalendar: Calendar,
    ActualInput,
    AppIcon,
    BaseModal,
    EventForm,
  },
  data() {
    return {
      schedules: [],
      projects: [],
      loading: false,
      selected: null,
      selectedProject: null,
      hoveredId: null,
      todayYmd: toYmd(new Date()),
      formOpen: false,
      formKey: 0,
      formInitial: {},
      editingId: null,
      submitting: false,
      deleting: false,
      serverErrors: {},
    };
  },
  computed: {
    // 日付ごとの表示内容。{ 'YYYY-MM-DD': [segment | null, ...] }（配列の添字が段）
    // 複数日にまたがる予定が週の中で同じ段に並ぶよう、週ごとに段を割り当てる
    layout() {
      const byDay = {};
      const weekLanes = {}; // 週の月曜日 -> 段ごとの最終使用日

      // プロジェクトの日付も、1日だけの予定と同じように段を割り当てる
      const milestones = this.projects
        .filter(project => project.milestone_date)
        .map(project => ({
          id: `project-${project.id}`,
          title: project.name,
          start_date: project.milestone_date,
          end_date: project.milestone_date,
          project,
        }));

      const sorted = [...milestones, ...this.schedules].sort((a, b) => {
        const endA = a.end_date || a.start_date;
        const endB = b.end_date || b.start_date;
        return a.start_date.localeCompare(b.start_date) // 開始が早い順
          || endB.localeCompare(endA) // 同じ開始日なら長い予定を上に
          || Number(!!b.project) - Number(!!a.project); // 同じ条件ならプロジェクトの日付を上に
      });

      sorted.forEach(schedule => {
        const start = parseYmd(schedule.start_date);
        const end = parseYmd(schedule.end_date || schedule.start_date);
        const startYmd = toYmd(start);
        const endYmd = toYmd(end);
        const cursor = new Date(start);

        while (cursor <= end) {
          // 週（月曜始まり）ごとに区切って段を決める
          const weekStart = addDays(cursor, -((cursor.getDay() + 6) % 7));
          const weekEnd = addDays(weekStart, 6);
          const segmentEnd = end < weekEnd ? end : weekEnd;
          const segmentStartYmd = toYmd(cursor);
          const segmentEndYmd = toYmd(segmentEnd);

          const lanes = (weekLanes[toYmd(weekStart)] ||= []);
          let lane = lanes.findIndex(lastYmd => lastYmd < segmentStartYmd);
          if (lane === -1) lane = lanes.length;
          lanes[lane] = segmentEndYmd;

          for (const day = new Date(cursor); day <= segmentEnd; day.setDate(day.getDate() + 1)) {
            const ymd = toYmd(day);
            // この日から週内の帯の終わりまでの日数（タイトルをその幅まで伸ばす）
            const span = Math.round((segmentEnd - day) / 86400000) + 1;
            const isStart = ymd === startYmd;
            const isRowStart = day.getDay() === 1;
            (byDay[ymd] ||= [])[lane] = {
              schedule,
              isStart,
              isEnd: ymd === endYmd,
              isRowStart,
              isRowEnd: day.getDay() === 0,
              span,
              // タイトルは開始日・週の頭・月初めにだけ出す
              showTitle: isStart || isRowStart || day.getDate() === 1,
            };
          }

          cursor.setTime(addDays(segmentEnd, 1).getTime());
        }
      });

      // 空いている段は高さを揃えるための空白にする
      Object.keys(byDay).forEach(ymd => {
        byDay[ymd] = Array.from(byDay[ymd], segment => segment || null);
      });

      return byDay;
    },
  },
  mounted() {
    this.fetchSchedules();
    this.fetchProjects();
  },
  methods: {
    actualOn,
    datesBetween,
    formatDateLong,
    formatDateShort,
    formatDifference,
    formatPeriod,
    formatWorkload,
    formatWorkloadUnit,
    isMilestonePast,
    milestoneCountdown,
    milestoneLabel,
    parseYmd,
    plannedOn,
    totalActual,
    totalDifference,
    workloadSummary,
    differenceClass,
    // 保存の完了時には詳細が閉じていることがあるので、対象の予定をここで固定しておく
    actualSaver(schedule, date) {
      return value => {
        const actuals = { ...schedule.actual_workloads };
        if (value === null) {
          delete actuals[date];
        } else {
          actuals[date] = value;
        }
        schedule.actual_workloads = actuals;
      };
    },
    chipStyle(color) {
      const background = color || DEFAULT_EVENT_COLOR;
      return { backgroundColor: background, color: readableTextColor(background) };
    },
    goToday() {
      this.$refs.calendar.move(new Date());
    },
    async fetchProjects() {
      try {
        const response = await axios.get('/api/projects');
        this.projects = response.data;
      } catch (error) {
        console.error('Error fetching projects:', error);
        toast('プロジェクトの読み込みに失敗しました。', 'error');
      }
    },
    projectOf(schedule) {
      return this.projects.find(project => project.id === schedule.project_id) || null;
    },
    onProjectCreated(project) {
      this.projects.push(project);
      this.projects.sort((a, b) => a.name.localeCompare(b.name, 'ja'));
    },
    async fetchSchedules() {
      this.loading = true;
      try {
        const response = await axios.get('/api/schedules');
        this.schedules = response.data;
      } catch (error) {
        console.error('Error fetching schedules:', error);
        toast('予定の読み込みに失敗しました。時間をおいて再度お試しください。', 'error');
      } finally {
        this.loading = false;
      }
    },
    openCreate(dateYmd) {
      const startDate = dateYmd || toYmd(new Date());
      this.editingId = null;
      this.formInitial = { startDate, endDate: startDate };
      this.showForm();
    },
    openDetails(schedule) {
      this.selected = schedule;
    },
    openEdit() {
      const schedule = this.selected;
      this.editingId = schedule.id;
      this.formInitial = {
        title: schedule.title,
        startDate: schedule.start_date,
        endDate: schedule.end_date,
        color: schedule.color || DEFAULT_EVENT_COLOR,
        projectId: schedule.project_id,
        workloadMode: schedule.workload_mode || 'none',
        workloadValue: schedule.workload_value,
        workloadUnit: schedule.workload_unit || '',
        dailyValues: { ...(schedule.daily_workloads || {}) },
        memo: schedule.memo || '',
      };
      this.selected = null;
      this.showForm();
    },
    showForm() {
      this.serverErrors = {};
      this.formKey += 1;
      this.formOpen = true;
    },
    closeForm() {
      if (!this.submitting) {
        this.formOpen = false;
      }
    },
    async saveSchedule(payload) {
      this.submitting = true;
      this.serverErrors = {};
      try {
        if (this.editingId) {
          const response = await axios.put(`/api/schedules/${this.editingId}`, payload);
          const index = this.schedules.findIndex(s => s.id === this.editingId);
          if (index !== -1) this.schedules.splice(index, 1, response.data);
          toast('予定を更新しました。');
        } else {
          const response = await axios.post('/api/schedules', payload);
          this.schedules.push(response.data);
          toast('予定を追加しました。');
        }
        this.formOpen = false;
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.serverErrors = error.response.data.errors || {};
        } else {
          console.error('Error saving schedule:', error);
          toast('保存に失敗しました。時間をおいて再度お試しください。', 'error');
        }
      } finally {
        this.submitting = false;
      }
    },
    async deleteSchedule() {
      const schedule = this.selected;
      const ok = await confirmDialog({
        title: '予定を削除しますか？',
        message: `「${schedule.title}」を削除します。この操作は元に戻せません。`,
        confirmLabel: '削除する',
        danger: true,
      });
      if (!ok) return;

      this.deleting = true;
      try {
        await axios.delete(`/api/schedules/${schedule.id}`);
        this.schedules = this.schedules.filter(s => s.id !== schedule.id);
        this.selected = null;
        toast('予定を削除しました。');
      } catch (error) {
        console.error('Error deleting schedule:', error);
        toast('削除に失敗しました。時間をおいて再度お試しください。', 'error');
      } finally {
        this.deleting = false;
      }
    },
  },
};
</script>
