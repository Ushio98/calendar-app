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
                            <option value="1">ページ</option>
                            <option value="2">文字</option>
                          </select>
                        </div>
                        <div class="form-group">
                          <label for="eventMemo">メモ:</label>
                          <textarea id="eventMemo" v-model="newEvent.memo"></textarea>
                        </div>
                        <div class="modal-actions">
                          <button @click="addEvent">保存</button>
                          <button @click="showAddEventModal = false">キャンセル</button>
                        </div>
                      </div>
                    </div>
                  
                    <!-- Edit Event Modal -->
                    <div v-if="showEditEventModal && editingEvent" class="modal-overlay">
                      <div class="modal-content">
                        <h3>イベントを編集</h3>
                        <div class="form-group">
                          <label for="editEventTitle">タイトル:</label>
                          <input type="text" id="editEventTitle" v-model="editingEvent.title" />
                        </div>
                        <div class="form-group">
                          <label for="editEventStartDate">開始日:</label>
                          <input type="date" id="editEventStartDate" v-model="editingEvent.startDate" />
                        </div>
                        <div class="form-group">
                          <label for="editEventEndDate">終了日:</label>
                          <input type="date" id="editEventEndDate" v-model="editingEvent.endDate" />
                        </div>
                        <div class="form-group">
                          <label for="editEventColor">色:</label>
                          <input type="color" id="editEventColor" v-model="editingEvent.color" />
                        </div>
                        <div class="form-group">
                          <label for="editWorkloadValue">作業量:</label>
                          <input type="number" id="editWorkloadValue" v-model.number="editingEvent.workloadValue" placeholder="例: 10" />
                          <select id="editWorkloadUnit" v-model="editingEvent.workloadUnit">
                            <option value="">単位を選択</option>
                            <option value="1">ページ</option>
                            <option value="2">文字</option>
                          </select>
                        </div>
                        <div class="form-group">
                          <label for="editEventMemo">メモ:</label>
                          <textarea id="editEventMemo" v-model="editingEvent.memo"></textarea>
                        </div>
                        <div class="modal-actions">
                          <button @click="updateEvent">保存</button>
                          <button @click="showEditEventModal = false">キャンセル</button>
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
                          <span>{{ selectedEvent.customData.workloadValue }} {{ formatWorkloadUnit(selectedEvent.customData.workloadUnit) }}</span>
                        </div>
                        <div class="event-details-group" v-if="selectedEvent.customData && selectedEvent.customData.memo">
                          <label>メモ:</label>
                          <span>{{ selectedEvent.customData.memo }}</span>
                        </div>
                        <div class="modal-actions">
                          <button @click="openEditModal" class="edit-button">編集</button>
                          <button @click="deleteEvent" class="delete-button">削除</button>
                          <button @click="showEventDetailsModal = false">閉じる</button>
                        </div>
                      </div>
                    </div>
                  </div></template>

<script>
import { Calendar, DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import axios from 'axios'; // Import axios

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
      showEditEventModal: false, // New data property for edit modal
      selectedEvent: null,
      editingEvent: null, // New data property for editing event
      newEvent: {
        title: '',
        startDate: '',
        endDate: '',
        color: '#60a5fa', // Default color
        workloadValue: null,
        workloadUnit: '',
        memo: '',
      },
      attributes: [], // Initialize as empty, data will be fetched
    };
  },
  methods: {
    addEvent() {
      if (!this.newEvent.title || !this.newEvent.startDate) {
        alert('タイトルと開始日は必須です。');
        return;
      }

      const payload = {
        title: this.newEvent.title,
        color: this.newEvent.color,
        memo: this.newEvent.memo,
        workload_value: this.newEvent.workloadValue,
        workload_unit: this.newEvent.workloadUnit,
        start_date: this.newEvent.startDate,
        end_date: this.newEvent.endDate,
        all_day: false, // Assuming all-day is not yet in the form, default to false
        location: null, // Assuming location is not yet in the form, default to null
      };

      axios.post('/api/schedules', payload)
        .then(response => {
          // Add the newly created schedule from the API response to the attributes
          const newSchedule = response.data;
          this.attributes.push({
            key: newSchedule.id,
            customData: {
              title: newSchedule.title,
              color: newSchedule.color,
              memo: newSchedule.memo,
              workloadValue: newSchedule.workload_value,
              workloadUnit: newSchedule.workload_unit,
              location: newSchedule.location,
            },
            dates: {
              start: new Date(newSchedule.start_date),
              end: new Date(newSchedule.end_date),
            },
          });
          this.resetNewEvent();
          this.showAddEventModal = false;
        })
        .catch(error => {
          console.error('Error adding schedule:', error.response.data);
          alert('スケジュールの追加に失敗しました。');
        });
    },
    resetNewEvent() {
      this.newEvent = {
        title: '',
        startDate: '',
        endDate: '',
        color: '#60a5fa',
        workloadValue: null,
        workloadUnit: '',
        memo: '',
      };
    },
    showDetails(attribute) {
      this.selectedEvent = attribute;
      this.showEventDetailsModal = true;
    },
    openEditModal() {
      this.editingEvent = {
        id: this.selectedEvent.key,
        title: this.selectedEvent.customData.title,
        startDate: this.selectedEvent.dates.start ? this.selectedEvent.dates.start.toISOString().split('T')[0] : '',
        endDate: this.selectedEvent.dates.end ? this.selectedEvent.dates.end.toISOString().split('T')[0] : '',
        color: this.selectedEvent.customData.color,
        workloadValue: this.selectedEvent.customData.workloadValue,
        workloadUnit: this.selectedEvent.customData.workloadUnit,
        memo: this.selectedEvent.customData.memo,
      };
      this.showEventDetailsModal = false;
      this.showEditEventModal = true;
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
    formatWorkloadUnit(unit) {
      switch (unit) {
        case 1:
          return 'ページ';
        case 2:
          return '文字';
        default:
          return unit; // Return original if not 1 or 2
      }
    },
    deleteEvent() {
      if (!confirm('本当にこのイベントを削除しますか？')) {
        return;
      }

      const scheduleId = this.selectedEvent.key;
      axios.delete(`/api/schedules/${scheduleId}`)
        .then(() => {
          this.attributes = this.attributes.filter(attr => attr.key !== scheduleId);
          this.showEventDetailsModal = false;
          this.selectedEvent = null;
          alert('イベントが削除されました。');
        })
        .catch(error => {
          console.error('Error deleting schedule:', error.response.data);
          alert('イベントの削除に失敗しました。');
        });
    },
    updateEvent() {
      if (!this.editingEvent.title || !this.editingEvent.startDate) {
        alert('タイトルと開始日は必須です。');
        return;
      }

      const payload = {
        title: this.editingEvent.title,
        color: this.editingEvent.color,
        memo: this.editingEvent.memo,
        workload_value: this.editingEvent.workloadValue,
        workload_unit: this.editingEvent.workloadUnit,
        start_date: this.editingEvent.startDate,
        end_date: this.editingEvent.endDate,
        all_day: false, // Assuming all-day is not yet in the form, default to false
        location: null, // Assuming location is not yet in the form, default to null
      };

      axios.put(`/api/schedules/${this.editingEvent.id}`, payload)
        .then(response => {
          // Update the event in the attributes array
          const updatedSchedule = response.data;
          const index = this.attributes.findIndex(attr => attr.key === updatedSchedule.id);
          if (index !== -1) {
            this.attributes.splice(index, 1, {
              key: updatedSchedule.id,
              customData: {
                title: updatedSchedule.title,
                color: updatedSchedule.color,
                memo: updatedSchedule.memo,
                workloadValue: updatedSchedule.workload_value,
                workloadUnit: updatedSchedule.workload_unit,
                location: updatedSchedule.location,
              },
              dates: {
                start: new Date(updatedSchedule.start_date),
                end: new Date(updatedSchedule.end_date),
              },
            });
          }
          this.showEditEventModal = false;
          this.editingEvent = null;
          alert('イベントが更新されました。');
        })
        .catch(error => {
          console.error('Error updating schedule:', error.response.data);
          alert('イベントの更新に失敗しました。');
        });
    },
    fetchSchedules() {
      axios.get('/api/schedules')
        .then(response => {
          this.attributes = response.data.map(schedule => {
            return {
              key: schedule.id,
              customData: {
                title: schedule.title,
                color: schedule.color,
                memo: schedule.memo,
                workloadValue: schedule.workload_value,
                workloadUnit: schedule.workload_unit,
                location: schedule.location,
              },
              dates: {
                start: new Date(schedule.start_date),
                end: new Date(schedule.end_date),
              },
            };
          });
        })
        .catch(error => {
          console.error('Error fetching schedules:', error);
        });
    },
  },
  mounted() {
    this.fetchSchedules();
  },
};
</script>