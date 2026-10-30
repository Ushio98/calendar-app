// サーバー側のルール（AppServiceProvider の Password::defaults）と合わせる
export function validatePassword(password) {
  if (!password) return 'パスワードを入力してください。';
  if (password.length < 8) return 'パスワードは8文字以上で入力してください。';
  if (!/[A-Za-z]/.test(password)) return 'パスワードには英字を1文字以上含めてください。';
  if (!/[0-9]/.test(password)) return 'パスワードには数字を1文字以上含めてください。';
  return '';
}
