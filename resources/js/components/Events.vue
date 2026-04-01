<template>
    <div class="events-page-container">
      <div class="header-container">
        <h2>イベントカレンダー</h2>
        <button @click="showAddEventModal = true" class="add-event-button">追加</button>
      </div>
      <div class="calendar-container">
        <VCalendar
          class="custom-calendar"
          :attributes="attributes"
          :first-day-of-week="2"
          :masks="{ title: 'YYYY年 MMM' }"
          is-expanded
          expanded
        >
          <template #day-content="{ day, attributes }">
            <div class="day-content-wrapper">
              <span class="day-label">{{ day.day }}</span>
              <div class="event-list">
                              <p
                                v-for="(attribute, index) in attributes"
                                :key="attribute.key || index"
                                class="event-item"
                                :style="{ backgroundColor: attribute.customData && attribute.customData.color }"
                                @click="showDetails(attribute)"
                              >
                                {{ attribute.customData && attribute.customData.title }}
                              </p>
                            </div>
                          </div>
                        </template>
                      </VCalendar>
                    </div>
                  
                    <!-- Add Event Modal -->
                    <div v-if="showAddEventModal" class="modal-overlay">
                      <div class="modal-content">
                        <h3>新しいイベントを追加</h3>
                        <div class="form-group">
                          <label for="eventTitle">タイトル:</label>
                          <input type="text" id="eventTitle" v-model="newEvent.title" />
                        </div>
                        <div class="form-group">
                          <label for="eventStartDate">開始日:</label>
                          <input type="date" id="eventStartDate" v-model="newEvent.startDate" />
                        </div>
                        <div class="form-group">
                          <label for="eventEndDate">終了日:</label>
                          <input type="date" id="eventEndDate" v-model="newEvent.endDate" />
                        </div>
                        <div class="form-group">
                          <label for="eventColor">色:</label>
                          <input type="color" id="eventColor" v-model="newEvent.color" />
                        </div>
                        <div class="form-group">
                          <label for="workloadValue">作業量:</label>
                          <input type="number" id="workloadValue" v-model.number="newEvent.workloadValue" placeholder="例: 10" />
                          <select id="workloadUnit" v-model="newEvent.workloadUnit">
                            <option value="">単位を選択</option>
                            <option value="ページ">ページ</option>
                            <option value="文字">文字</option>
                          </select>
                        </div>
                        <div class="modal-actions">
                          <button @click="addEvent">保存</button>
                          <button @click="showAddEventModal = false">キャンセル</button>
                        </div>
                      </div>
                    </div>
                  
                    <!-- Event Details Modal -->
                    <div v-if="showEventDetailsModal && selectedEvent" class="modal-overlay">
                      <div class="modal-content">
                        <h3>イベント詳細</h3>
                        <div class="event-details-group">
                          <label>タイトル:</label>
                          <span>{{ selectedEvent.customData && selectedEvent.customData.title }}</span>
                        </div>
                        <div class="event-details-group">
                          <label>日付:</label>
                          <span>{{ formatEventDates(selectedEvent.dates) }}</span>
                        </div>
                        <div class="event-details-group" v-if="selectedEvent.customData && selectedEvent.customData.workloadValue">
                          <label>作業量:</label>
                          <span>{{ selectedEvent.customData.workloadValue }} {{ selectedEvent.customData.workloadUnit }}</span>
                        </div>
                        <div class="modal-actions">
                          <button @click="showEventDetailsModal = false">閉じる</button>
                        </div>
                      </div>
                    </div>
                  </div></template>

<script>
import { Calendar, DatePicker } from 'v-calendar';
import 'v-calendar/style.css';

export default {
  name: 'Events',
  components: {
    VCalendar: Calendar,
    VDatePicker: DatePicker,
  },
  data() {
    return {
      selectedDate: new Date(),
      showAddEventModal: false,
      showEventDetailsModal: false,
      selectedEvent: null,
      newEvent: {
        title: '',
        startDate: '',
        endDate: '',
        color: '#60a5fa', // Default color
        workloadValue: null,
        workloadUnit: '',
      },
      attributes: [
        {
          key: 'today',
          highlight: {
            color: 'blue',
            fillMode: 'light',
          },
          dates: new Date(),
        },
        {
          key: 'event1',
          customData: {
            title: '会議',
            color: '#f87171', // 赤
          },
          dates: new Date(2026, 2, 15), // 2026年3月15日 (Month is 0-indexed, so 2 is March)
        },
        {
          key: 'event2',
          customData: {
            title: 'プロジェクト締め切り',
            color: '#34d399', // 緑
          },
          dates: new Date(2026, 2, 20), // 2026年3月20日 (Month is 0-indexed, so 2 is March)
        },
        {
          key: 'event3',
          customData: {
            title: 'チームランチ',
            color: '#60a5fa',
          },
          dates: new Date(2026, 2, 20), // 2026年3月20日 (Month is 0-indexed, so 2 is March)
        },
      ],
    };
  },
  methods: {
    addEvent() {
      if (!this.newEvent.title || !this.newEvent.startDate) {
        alert('タイトルと開始日は必須です。');
        return;
      }

      let eventDates;
      if (this.newEvent.endDate) {
        eventDates = {
          start: new Date(this.newEvent.startDate),
          end: new Date(this.newEvent.endDate),
        };
      } else {
        eventDates = new Date(this.newEvent.startDate);
      }

      const customData = {
        title: this.newEvent.title,
        color: this.newEvent.color,
      };

      if (this.newEvent.workloadValue !== null && this.newEvent.workloadValue !== '') {
        customData.workloadValue = this.newEvent.workloadValue;
        customData.workloadUnit = this.newEvent.workloadUnit;
      }

      this.attributes.push({
        key: `event-${Date.now()}`, // Unique key
        customData: customData,
        dates: eventDates,
      });

      this.resetNewEvent();
      this.showAddEventModal = false;
    },
    resetNewEvent() {
      this.newEvent = {
        title: '',
        startDate: '',
        endDate: '',
        color: '#60a5fa',
        workloadValue: null,
        workloadUnit: '',
      };
    },
    showDetails(attribute) {
      this.selectedEvent = attribute;
      this.showEventDetailsModal = true;
    },
    formatEventDates(dates) {
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      if (dates.start && dates.end) {
        const start = new Date(dates.start).toLocaleDateString(undefined, options);
        const end = new Date(dates.end).toLocaleDateString(undefined, options);
        return `${start} - ${end}`;
      } else if (dates instanceof Date) {
        return dates.toLocaleDateString(undefined, options);
      }
      return '';
    },
  },
  mounted() {
    // Debugging: Log all attributes to see their structure
    console.log('All attributes:', this.attributes);
  },
};
</script>