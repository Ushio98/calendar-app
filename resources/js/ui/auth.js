import { reactive } from 'vue';
import axios from 'axios';

// ログイン中のユーザー。loaded はサーバーへの確認が済んだかどうか
export const authState = reactive({
  user: null,
  loaded: false,
});

let pending = null;

export function fetchUser() {
  if (!pending) {
    pending = axios.get('/auth/user')
      .then(response => {
        authState.user = response.data.user;
      })
      .catch(() => {
        authState.user = null;
      })
      .finally(() => {
        authState.loaded = true;
        pending = null;
      });
  }
  return pending;
}

export async function login(credentials) {
  const response = await axios.post('/auth/login', credentials);
  authState.user = response.data.user;
}

export async function register(data) {
  const response = await axios.post('/auth/register', data);
  authState.user = response.data.user;
}

export async function logout() {
  try {
    await axios.post('/auth/logout');
  } finally {
    authState.user = null;
  }
}

export function clearUser() {
  authState.user = null;
}

// axios のエラーから、画面に出すメッセージを取り出す
export function errorsFrom(error) {
  const response = error && error.response;
  if (response && response.status === 422) {
    return { fields: response.data.errors || {}, message: '' };
  }
  if (response && response.status === 429) {
    return { fields: {}, message: '試行回数が多すぎます。1分ほど待ってから再度お試しください。' };
  }
  if (response && response.status === 419) {
    return { fields: {}, message: '画面の有効期限が切れました。ページを再読み込みしてからお試しください。' };
  }
  return { fields: {}, message: '通信に失敗しました。時間をおいて再度お試しください。' };
}

// ログイン後の戻り先。外部サイトへ飛ばされないよう、アプリ内のパスだけを許可する
export function safeRedirect(path) {
  return typeof path === 'string' && path.startsWith('/') && !path.startsWith('//') ? path : '/';
}
