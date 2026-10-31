<template>
  <section class="page">
    <div class="page-header">
      <div>
        <p class="page-eyebrow">{{ todayLabel }}</p>
        <h2 class="page-title">{{ greeting }}</h2>
      </div>
      <router-link to="/events" class="btn btn--ghost">
        <AppIcon name="calendar" :size="18" />カレンダーを開く
      </router-link>
    </div>

    <div class="stats">
      <div class="stat">
        <span class="stat__label">本日の予定</span>
        <span class="stat__value">{{ schedules.length }}<small>件</small></span>
      </div>
      <div class="stat">
        <span class="stat__label">本日の作業</span>
        <span class="stat__value">{{ doneCount }}<small> / {{ tasks.length }} 完了</small></span>
        <div class="progress" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
          <div class="progress__bar" :style="{ width: `${progress}%` }"></div>
        </div>
      </div>
    </div>

    <div class="dashboard-grid">
      <div class="card">
        <header class="card__header">
          <h3 class="card__title"><AppIcon name="calendar" :size="20" />本日の予定</h3>
        </header>

        <p v-if="loading" class="card__placeholder">読み込み中…</p>
        <ul v-else-if="schedules.length" class="schedule-list">
          <li v-for="schedule in schedules" :key="schedule.id" class="schedule-row" :style="{ '--event-color': schedule.color || defaultColor }">
            <div class="schedule-row__main">
              <p v-if="schedule.project" class="schedule-row__project">
                <AppIcon name="folder" :size="13" />{{ schedule.project.name }}
              </p>
              <p class="schedule-row__title">{{ schedule.title }}</p>
              <p class="schedule-row__meta">
                <AppIcon name="clock" :size="14" />{{ formatPeriod(schedule.start_date, schedule.end_date) }}
              </p>
              <p v-if="schedule.memo" class="schedule-row__memo">{{ schedule.memo }}</p>
            </div>
          </li>
        </ul>
        <div v-else class="empty-state">
          <p>本日の予定はありません。</p>
          <router-link to="/events" class="btn btn--soft btn--sm"><AppIcon name="plus" :size="16" />予定を追加する</router-link>
        </div>
      </div>

      <div class="card">
        <header class="card__header">
          <h3 class="card__title"><AppIcon name="layers" :size="20" />本日の作業</h3>
          <span v-if="tasks.length" class="badge">{{ progress }}%</span>
        </header>

        <p v-if="loading" class="card__placeholder">読み込み中…</p>
        <ul v-else-if="tasks.length" class="task-list">
          <li v-for="task in tasks" :key="task.id" class="task" :class="{ 'is-done': task.is_checked }">
            <label class="task__check">
              <input
                type="checkbox"
                class="task__input"
                :checked="task.is_checked"
                :disabled="pending[task.id]"
                @change="toggleTask(task, $event.target.checked)"
              />
              <span class="task__box"><AppIcon name="check" :size="14" /></span>
              <span class="task__title">{{ task.title }}</span>
            </label>
            <div v-if="task.today_workload !== null" class="task__progress">
              <span class="task__progress-label">実績</span>
              <ActualInput
                :schedule-id="task.id"
                :date="todayYmd"
                :value="actualOn(task, todayYmd)"
                :label="`${task.title}の今日の実績`"
:on-saved="value => setActual(task, value)"
              />
              <span class="task__plan">/ {{ formatWorkload(task.today_workload, task.workload_unit) }}</span>
              <span
                v-if="actualOn(task, todayYmd) !== null"
                class="progress-diff"
                :class="differenceClass(actualOn(task, todayYmd) - task.today_workload)"
              >{{ formatDifference(actualOn(task, todayYmd) - task.today_workload) }}</span>
            </div>
          </li>
        </ul>
        <div v-else class="empty-state">
          <p>本日の作業はありません。</p>
          <p class="empty-state__hint">予定の「本日の作業に表示する」をオンにすると、ここに表示されます。</p>
        </div>
      </div>
    </div>

    <div class="card">
      <header class="card__header">
        <h3 class="card__title"><AppIcon name="folder" :size="20" />進行中のプロジェクト</h3>
        <router-link to="/projects" class="text-link text-link--small">すべて見る</router-link>
      </header>

      <p v-if="projectsLoading" class="card__placeholder">読み込み中…</p>
      <div v-else-if="activeProjects.length" class="active-projects">
        <article v-for="project in activeProjects" :key="project.id" class="active-project">
          <h4 class="active-project__name">{{ project.name }}</h4>
          <p
            v-if="project.milestone_date"
            class="project-milestone"
            :class="{ 'is-past': isMilestonePast(project.milestone_date) }"
          >
            <AppIcon name="flag" :size="16" />
            <span class="project-milestone__type">{{ milestoneLabel(project.milestone_type) }}</span>
            <span>{{ formatDateShort(parseYmd(project.milestone_date)) }}</span>
            <span class="project-milestone__countdown">{{ milestoneCountdown(project.milestone_date) }}</span>
          </p>
          <ProjectProgress :project="project" />
        </article>
      </div>
      <div v-else class="empty-state">
        <p>進行中のプロジェクトはありません。</p>
        <p class="empty-state__hint">プロジェクトに予定を入れると、期間中はここに表示されます。</p>
      </div>
    </div>
  </section>
</template>

<script>
import ActualInput from './ActualInput.vue';
import ProjectProgress from './ProjectProgress.vue';
import AppIcon from './ui/AppIcon.vue';
import { toast } from '../ui/toast';
import { authState } from '../ui/auth';
import { activeProjects, isMilestonePast, milestoneCountdown, milestoneLabel } from '../ui/project';
import {
  actualOn,
  DEFAULT_EVENT_COLOR,
  differenceClass,
  formatDateLong,
  formatDateShort,
  formatDifference,
  formatPeriod,
  formatWorkload,
  parseYmd,
  toYmd,
} from '../ui/schedule';

export default {
  name: 'Dashboard',
  components: { ActualInput, AppIcon, ProjectProgress },
  data() {
    return {
      schedules: [],
      projects: [],
      projectsLoading: true,
      loading: true,
      pending: {},
      defaultColor: DEFAULT_EVENT_COLOR,
      todayYmd: toYmd(new Date()),
    };
  },
  computed: {
    activeProjects() {
      return activeProjects(this.projects);
    },
    todayLabel() {
      return formatDateLong(new Date());
    },
    greeting() {
      const hour = new Date().getHours();
      let greeting = 'こんばんは';
      if (hour >= 5 && hour < 11) greeting = 'おはようございます';
      else if (hour >= 11 && hour < 18) greeting = 'こんにちは';
      return authState.user ? `${greeting}、${authState.user.name}さん` : greeting;
    },
    tasks() {
      return this.schedules.filter(s => s.is_today_task);
    },
    doneCount() {
      return this.tasks.filter(t => t.is_checked).length;
    },
    progress() {
      return this.tasks.length ? Math.round((this.doneCount / this.tasks.length) * 100) : 0;
    },
  },
  mounted() {
    this.fetchTodaySchedules();
    this.fetchProjects();
  },
  methods: {
    actualOn,
    formatDateShort,
    formatDifference,
    formatPeriod,
    isMilestonePast,
    milestoneCountdown,
    milestoneLabel,
    parseYmd,
    formatWorkload,
    differenceClass,
    setActual(task, value) {
      const actuals = { ...task.actual_workloads };
      if (value === null) {
        delete actuals[this.todayYmd];
      } else {
        actuals[this.todayYmd] = value;
      }
      task.actual_workloads = actuals;

      // 同じ予定はプロジェクト側にも別のデータとしてあるので、進み具合にもすぐ反映する
      const inProject = this.projects
        .flatMap(project => project.schedules)
        .find(schedule => schedule.id === task.id);
      if (inProject) inProject.actual_workloads = actuals;
    },
    async fetchProjects() {
      this.projectsLoading = true;
      try {
        const response = await window.axios.get('/api/projects');
        this.projects = response.data;
      } catch (error) {
        console.error('Error fetching projects:', error);
        toast('進行中のプロジェクトの読み込みに失敗しました。', 'error');
      } finally {
        this.projectsLoading = false;
      }
    },
    async fetchTodaySchedules() {
      this.loading = true;
      try {
        const response = await window.axios.get('/api/schedules/today');
        this.schedules = response.data;
      } catch (error) {
        console.error("Error fetching today's schedules:", error);
        toast('本日の予定の読み込みに失敗しました。', 'error');
      } finally {
        this.loading = false;
      }
    },
    async toggleTask(task, checked) {
      // 先に画面へ反映し、失敗したら元に戻す
      task.is_checked = checked;
      this.pending[task.id] = true;
      try {
        await window.axios.put(`/api/schedules/${task.id}/check`, { checked });
        if (checked && this.doneCount === this.tasks.length) {
          toast('本日の作業がすべて完了しました。おつかれさまでした！');
        }
      } catch (error) {
        console.error('Error updating task:', error);
        task.is_checked = !checked;
        toast('チェック状態を保存できませんでした。', 'error');
      } finally {
        this.pending[task.id] = false;
      }
    },
  },
};
</script>
