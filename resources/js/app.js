import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import App from './App.vue';
import 'v-calendar/style.css'; // v-calendarのスタイルをインポート

// コンポーネントのインポート
import Dashboard from './components/Dashboard.vue';
import Events from './components/Events.vue';
import Projects from './components/Projects.vue';
import Settings from './components/Settings.vue';
import LoginPage from './components/auth/LoginPage.vue';
import RegisterPage from './components/auth/RegisterPage.vue';
import ForgotPasswordPage from './components/auth/ForgotPasswordPage.vue';
import ResetPasswordPage from './components/auth/ResetPasswordPage.vue';
import { authState, clearUser, fetchUser } from './ui/auth';
import { toast } from './ui/toast';

// ルートの定義
// meta.guest: ログイン前に使う画面（ログイン済みならダッシュボードへ）
// meta.public: ログインの有無に関係なく開ける画面
const routes = [
  { path: '/', component: Dashboard, meta: { title: 'ダッシュボード' } },
  { path: '/events', component: Events, meta: { title: 'カレンダー' } },
  { path: '/projects', component: Projects, meta: { title: 'プロジェクト' } },
  { path: '/settings', component: Settings, meta: { title: '設定' } },
  { path: '/login', component: LoginPage, meta: { title: 'ログイン', guest: true } },
  { path: '/register', component: RegisterPage, meta: { title: '新規登録', guest: true } },
  { path: '/forgot-password', component: ForgotPasswordPage, meta: { title: 'パスワードの再設定', guest: true } },
  { path: '/reset-password/:token', component: ResetPasswordPage, meta: { title: '新しいパスワードの設定', public: true } },
  { path: '/:pathMatch(.*)*', redirect: '/' },
];

// ルーターの作成
const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async to => {
  if (!authState.loaded) {
    await fetchUser();
  }

  if (to.meta.public) return true;

  if (to.meta.guest) {
    return authState.user ? '/' : true;
  }

  if (!authState.user) {
    return { path: '/login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } };
  }
  return true;
});

router.afterEach(to => {
  document.title = to.meta.title ? `${to.meta.title} | Tsumugu（つむぐ）` : 'Tsumugu（つむぐ）';
});

// セッション切れなどで API が 401 を返したら、ログイン画面へ戻す
axios.interceptors.response.use(
  response => response,
  error => {
    const status = error.response && error.response.status;
    const url = (error.config && error.config.url) || '';
    if (status === 401 && url.startsWith('/api/') && authState.user) {
      clearUser();
      toast('ログインの有効期限が切れました。もう一度ログインしてください。', 'info');
      router.replace({ path: '/login', query: { redirect: router.currentRoute.value.fullPath } });
    }
    return Promise.reject(error);
  }
);

const app = createApp(App);
app.use(router);
app.mount('#app');
