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
          <li v-for="task in tasks" :key="task.id">
            <label class="task" :class="{ 'is-done': task.is_checked }">
              <input
                type="checkbox"
                class="task__input"
                :checked="task.is_checked"
                :disabled="pending[task.id]"
                @change="toggleTask(task, $event.target.checked)"
              />
              <span class="task__box"><AppIcon name="check" :size="14" /></span>
              <span class="task__title">{{ task.title }}</span>
              <span class="task__workload">
                {{ formatWorkload(task.today_workload, task.workload_unit) }}
                <small v-if="task.workload_mode === 'daily'">今日の分</small>
              </span>
            </label>
          </li>
        </ul>
        <div v-else class="empty-state">
          <p>本日の作業はありません。</p>
          <p class="empty-state__hint">予定に「作業量」を入れると、ここに表示されます。</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import AppIcon from './ui/AppIcon.vue';
import { toast } from '../ui/toast';
import { authState } from '../ui/auth';
import { DEFAULT_EVENT_COLOR, formatDateLong, formatPeriod, formatWorkload } from '../ui/schedule';

export default {
  name: 'Dashboard',
  components: { AppIcon },
  data() {
    return {
      schedules: [],
      loading: true,
      pending: {},
      defaultColor: DEFAULT_EVENT_COLOR,
    };
  },
  computed: {
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
      // 「日ごとに設定」で今日の作業量が空欄の予定は、今日は作業なし
      return this.schedules.filter(s => s.today_workload !== null && s.today_workload !== undefined);
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
  },
  methods: {
    formatPeriod,
    formatWorkload,
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
