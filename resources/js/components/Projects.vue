<template>
  <section class="page">
    <div class="page-header">
      <div>
        <h2 class="page-title">プロジェクト</h2>
        <p class="page-subtitle">予定をプロジェクトごとにまとめ、納品日などの日付を管理できます。</p>
      </div>
      <button type="button" class="btn btn--primary" @click="openForm(null)">
        <AppIcon name="plus" :size="18" />プロジェクトを追加
      </button>
    </div>

    <p v-if="loading" class="card__placeholder">読み込み中…</p>

    <div v-else-if="projects.length" class="project-grid">
      <article v-for="project in projects" :key="project.id" class="card project-card">
        <header class="project-card__header">
          <h3 class="project-card__name">{{ project.name }}</h3>
          <div class="project-card__actions">
            <button type="button" class="icon-button" :aria-label="`${project.name}を編集`" @click="openForm(project)">
              <AppIcon name="edit" :size="18" />
            </button>
            <button type="button" class="icon-button" :aria-label="`${project.name}を削除`" @click="deleteProject(project)">
              <AppIcon name="trash" :size="18" />
            </button>
          </div>
        </header>

        <p v-if="project.milestone_date" class="project-milestone" :class="{ 'is-past': isMilestonePast(project.milestone_date) }">
          <AppIcon name="flag" :size="16" />
          <span class="project-milestone__type">{{ milestoneLabel(project.milestone_type) }}</span>
          <span>{{ formatDateLong(parseYmd(project.milestone_date)) }}</span>
          <span class="project-milestone__countdown">{{ milestoneCountdown(project.milestone_date) }}</span>
        </p>
        <p v-else class="project-milestone project-milestone--empty">日付は設定されていません</p>

        <h4 class="project-card__subtitle">予定 {{ project.schedules.length }}件</h4>
        <ul v-if="project.schedules.length" class="project-schedules">
          <li v-for="schedule in project.schedules" :key="schedule.id" :style="{ '--event-color': schedule.color || defaultColor }">
            <span class="project-schedules__title">{{ schedule.title }}</span>
            <span class="project-schedules__period">{{ formatPeriod(schedule.start_date, schedule.end_date) }}</span>
          </li>
        </ul>
        <p v-else class="project-card__empty">カレンダーで予定を追加するときに、このプロジェクトを選べます。</p>
      </article>
    </div>

    <div v-else class="empty-state">
      <p>プロジェクトはまだありません。</p>
      <button type="button" class="btn btn--soft btn--sm" @click="openForm(null)">
        <AppIcon name="plus" :size="16" />プロジェクトを追加する
      </button>
    </div>

    <ProjectFormModal :open="formOpen" :project="editing" @close="formOpen = false" @saved="onSaved" />
  </section>
</template>

<script>
import axios from 'axios';
import AppIcon from './ui/AppIcon.vue';
import ProjectFormModal from './ProjectFormModal.vue';
import { confirmDialog } from '../ui/confirm';
import { toast } from '../ui/toast';
import { isMilestonePast, milestoneCountdown, milestoneLabel } from '../ui/project';
import { DEFAULT_EVENT_COLOR, formatDateLong, formatPeriod, parseYmd } from '../ui/schedule';

export default {
  name: 'Projects',
  components: { AppIcon, ProjectFormModal },
  data() {
    return {
      projects: [],
      loading: true,
      formOpen: false,
      editing: null,
      defaultColor: DEFAULT_EVENT_COLOR,
    };
  },
  mounted() {
    this.fetchProjects();
  },
  methods: {
    formatDateLong,
    formatPeriod,
    isMilestonePast,
    milestoneCountdown,
    milestoneLabel,
    parseYmd,
    async fetchProjects() {
      this.loading = true;
      try {
        const response = await axios.get('/api/projects');
        this.projects = response.data;
      } catch (error) {
        console.error('Error fetching projects:', error);
        toast('プロジェクトの読み込みに失敗しました。', 'error');
      } finally {
        this.loading = false;
      }
    },
    openForm(project) {
      this.editing = project;
      this.formOpen = true;
    },
    onSaved(project) {
      const index = this.projects.findIndex(item => item.id === project.id);
      if (index !== -1) {
        this.projects.splice(index, 1, project);
      } else {
        this.projects.push(project);
      }
      this.projects.sort((a, b) => a.name.localeCompare(b.name, 'ja'));
      this.formOpen = false;
    },
    async deleteProject(project) {
      const ok = await confirmDialog({
        title: 'プロジェクトを削除しますか？',
        message: `「${project.name}」を削除します。予定は削除されず、プロジェクトの設定だけが外れます。`,
        confirmLabel: '削除する',
        danger: true,
      });
      if (!ok) return;

      try {
        await axios.delete(`/api/projects/${project.id}`);
        this.projects = this.projects.filter(item => item.id !== project.id);
        toast('プロジェクトを削除しました。');
      } catch (error) {
        console.error('Error deleting project:', error);
        toast('削除に失敗しました。時間をおいて再度お試しください。', 'error');
      }
    },
  },
};
</script>
