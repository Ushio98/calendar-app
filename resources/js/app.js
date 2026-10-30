import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import 'v-calendar/style.css'; // v-calendarのスタイルをインポート

// コンポーネントのインポート
import Dashboard from './components/Dashboard.vue'; // 仮のコンポーネント
import Events from './components/Events.vue';     // 仮のコンポーネント
import Settings from './components/Settings.vue';   // 仮のコンポーネント

// ルートの定義
const routes = [
  { path: '/', component: Dashboard, meta: { title: 'ダッシュボード' } },
  { path: '/events', component: Events, meta: { title: 'カレンダー' } },
  { path: '/settings', component: Settings, meta: { title: '設定' } },
];

// ルーターの作成
const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.afterEach(to => {
  document.title = to.meta.title ? `${to.meta.title} | Tsumugu（つむぐ）` : 'Tsumugu（つむぐ）';
});

const app = createApp(App);
app.use(router);
app.mount('#app');