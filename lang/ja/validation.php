<?php

return [
    'required' => ':attributeは必須です。',
    'string' => ':attributeは文字列で入力してください。',
    'integer' => ':attributeは整数で入力してください。',
    'size' => [
        'string' => ':attributeは:size文字で入力してください。',
    ],
    'unique' => ':attributeは既に使用されています。',
    'date' => ':attributeには有効な日付を入力してください。',
    'url' => ':attributeには有効なURLを入力してください。',
    'array' => ':attributeは配列で入力してください。',
    'exists' => '選択された:attributeは存在しません。',
    'min' => [
        'numeric' => ':attributeは:min以上で入力してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'max' => [
        'numeric' => ':attributeは:max以下で入力してください。',
    ],

    'attributes' => [
        'title' => 'タイトル',
        'author' => '著者',
        'isbn' => 'ISBN',
        'published_date' => '出版日',
        'description' => '説明',
        'image_url' => '画像URL',
        'genres' => 'ジャンル',
        'genres.*' => 'ジャンル',
        'name' => 'ジャンル名',
        'rating' => '評価',
        'comment' => 'コメント',
        'password' => 'パスワード',
        'user_id' => 'ユーザーID',
    ],
];
