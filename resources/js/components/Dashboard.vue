<template>
  <div class="dashboard-container">
    <h2>ダッシュボード</h2>

    <div class="schedules-and-tasks-container">
      <div class="today-schedules-section">
        <h3>本日の予定</h3>
        <div v-if="todaySchedules.length > 0" class="schedules-list">
          <div v-for="schedule in todaySchedules" :key="schedule.id" class="schedule-item">
            <div class="schedule-title">{{ schedule.title }}</div>
            <div class="schedule-date">{{ formatDate(schedule.start_date) }} - {{ formatDate(schedule.end_date) }}</div>
            <p class="schedule-description">{{ schedule.description }}</p>
          </div>
        </div>
        <div v-else class="no-schedules">
          <p>本日の予定はありません。</p>
        </div>
      </div>

      <div class="today-schedules-section">
        <h3>本日の作業</h3>
        <div v-if="todayTasks.length > 0" class="schedules-list">
          <div v-for="task in todayTasks" :key="task.id" class="schedule-item">
            <div class="schedule-title">
              <input type="checkbox" :id="'task-' + task.id" v-model="task.is_checked">
              <label :for="'task-' + task.id">{{ task.title }}</label>
            </div>
                      <div class="schedule-date task-workload-highlight">
                        (作業量: {{ task.workload_value }}{{ formatWorkloadUnit(task.workload_unit) }})
                      </div>          </div>
        </div>
        <div v-else class="no-schedules">
          <p>本日の作業はありません。</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Dashboard',
  data() {
    return {
      todaySchedules: [],
      checkedTasks: [], // 新しく追加
    };
  },
  computed: { // 新しく追加
    todayTasks() {
      return this.todaySchedules.filter(schedule =>
        schedule.workload_value !== undefined && schedule.workload_value !== null
      );
    },
  },
  mounted() {
    this.fetchTodaySchedules();
  },
  methods: {
    fetchTodaySchedules() {
      window.axios.get('/api/schedules/today')
        .then(response => {
          this.todaySchedules = response.data.map(schedule => ({
            ...schedule,
            is_checked: false, // 新しく追加
          }));
        })
        .catch(error => {
          console.error('Error fetching today\'s schedules:', error);
          // Optionally, display an error message to the user
        });
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}年${month}月${day}日`;
    },
    formatWorkloadUnit(unit) {
      switch (unit) {
        case 1:
          return 'ページ';
        case 2:
          return '文字';
        default:
          return ''; // または不明な単位を示す文字列
      }
    },
  },
};
</script>