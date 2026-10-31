<template>
  <section class="project-progress" aria-label="進み具合">
    <h4 class="project-card__subtitle">進み具合</h4>
    <div v-for="group in groups" :key="group.key" class="project-progress__group">
      <p class="project-progress__numbers">
        <span>
          <strong>{{ group.actual }}</strong> / {{ group.planned }}{{ group.unit }}
        </span>
        <span v-if="group.percent !== null" class="project-progress__percent">{{ group.percent }}%</span>
      </p>
      <div
        v-if="group.percent !== null"
        class="progress project-progress__bar"
        role="progressbar"
        :aria-valuenow="Math.min(group.percent, 100)"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        <div class="progress__bar" :style="{ width: `${Math.min(group.percent, 100)}%` }"></div>
        <span
          v-if="group.paceMark > 0 && group.paceMark < 100"
          class="project-progress__pace"
          :style="{ left: `${group.paceMark}%` }"
          aria-hidden="true"
        ></span>
      </div>
      <p v-if="group.started" class="project-progress__pace-text">
        今日までの予定 {{ group.plannedToDate }}{{ group.unit }}
        <span class="progress-diff" :class="differenceClass(group.difference)">
          差 {{ formatDifference(group.difference) }}
        </span>
      </p>
      <p v-else class="project-progress__pace-text">作業量のある予定は、まだ始まっていません</p>
    </div>
    <p v-if="!groups.length" class="project-card__empty">
      作業量が設定された予定がないため、進み具合は表示されません。
    </p>
  </section>
</template>

<script>
import { projectProgress } from '../ui/project';
import { differenceClass, formatDifference } from '../ui/schedule';

export default {
  name: 'ProjectProgress',
  props: {
    project: { type: Object, required: true },
  },
  computed: {
    groups() {
      return projectProgress(this.project);
    },
  },
  methods: {
    differenceClass,
    formatDifference,
  },
};
</script>
