<?php

// このアプリで使っているルールの日本語メッセージ。
// ここにないルールは英語（フレームワーク標準）で表示される
return [
    'after_or_equal' => ':attributeは:date以降の日付にしてください。',
    'boolean' => ':attributeの値が正しくありません。',
    'confirmed' => ':attributeが確認用と一致しません。',
    'date' => ':attributeは正しい日付を入力してください。',
    'email' => ':attributeは正しいメールアドレスの形式で入力してください。',
    'integer' => ':attributeは整数で入力してください。',
    'max' => [
        'numeric' => ':attributeは:max以下で入力してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'min' => [
        'numeric' => ':attributeは:min以上で入力してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'password' => [
        'letters' => ':attributeには英字を1文字以上含めてください。',
        'mixed' => ':attributeには大文字と小文字をそれぞれ1文字以上含めてください。',
        'numbers' => ':attributeには数字を1文字以上含めてください。',
        'symbols' => ':attributeには記号を1文字以上含めてください。',
        'uncompromised' => 'この:attributeは過去に流出したことがあるため使えません。別の:attributeにしてください。',
    ],
    'required' => ':attributeを入力してください。',
    'string' => ':attributeは文字列で入力してください。',
    'unique' => 'この:attributeはすでに使われています。',

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'token' => '再設定トークン',
        'title' => 'タイトル',
        'color' => '色',
        'memo' => 'メモ',
        'workload_value' => '作業量',
        'workload_unit' => '作業量の単位',
        'start_date' => '開始日',
        'end_date' => '終了日',
        'location' => '場所',
        'checked' => 'チェック状態',
    ],
];
