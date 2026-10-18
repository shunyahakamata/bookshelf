<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = [
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784101010014',
                'rating' => 5,
                'comment' => '猫の視点から描かれる人間社会が面白かったです。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784101010014',
                'rating' => 4,
                'comment' => '独特な語り口が印象に残りました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784101010014',
                'rating' => 4,
                'comment' => '昔の作品ですが、今読んでも楽しめました。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784422100524',
                'rating' => 3,
                'comment' => '人との接し方を見直すきっかけになる内容でした。',
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784422100524',
                'rating' => 4,
                'comment' => '具体的なエピソードが多く、人間関係について分かりやすく学べました。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784422100524',
                'rating' => 3,
                'comment' => '日常のコミュニケーションで実践できそうな考え方が参考になりました。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784873115658',
                'rating' => 5,
                'comment' => '読みやすいコードを書くための具体的な工夫が多く、とても参考になりました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784873115658',
                'rating' => 4,
                'comment' => '変数名や関数の書き方など、すぐに実践できる内容が多かったです。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784873115658',
                'rating' => 5,
                'comment' => 'コードは動くだけでなく、他の人が読みやすいことも重要だと実感しました。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784863940246',
                'rating' => 3,
                'comment' => '日々の行動や考え方を見直すきっかけになる内容でした。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784863940246',
                'rating' => 4,
                'comment' => '仕事だけでなく普段の生活にも活かせそうな考え方が多く参考になりました。',
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784863940246',
                'rating' => 3,
                'comment' => '内容は少し難しい部分もありましたが、長期的な目標について考えるきっかけになりました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784101010021',
                'rating' => 4,
                'comment' => '主人公のまっすぐな性格とテンポの良い展開が面白かったです。',
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784101010021',
                'rating' => 3,
                'comment' => '登場人物のやり取りが印象的で、楽しみながら読むことができました。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784101010021',
                'rating' => 4,
                'comment' => '古い作品ですが文章のテンポが良く、思っていたより読みやすかったです。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784309226712',
                'rating' => 3,
                'comment' => '人類の歴史を大きな視点から捉えていて、新しい見方を知ることができました。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784309226712',
                'rating' => 3,
                'comment' => '普段当たり前だと思っている社会の仕組みについて考えさせられました。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784309226712',
                'rating' => 3,
                'comment' => '扱っているテーマが壮大で、歴史と科学の両方に興味が湧く内容でした。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784048930598',
                'rating' => 4,
                'comment' => '保守しやすいコードを書くための考え方が具体的に説明されていて勉強になりました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784048930598',
                'rating' => 5,
                'comment' => 'プログラムを書くときに意識すべきことが多く、繰り返し読みたいと思いました。',
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784048930598',
                'rating' => 4,
                'comment' => '読みやすさを意識した設計や命名の重要性を改めて理解できました。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784478025819',
                'rating' => 3,
                'comment' => '人間関係についてこれまでとは違った視点から考えるきっかけになりました。',
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784478025819',
                'rating' => 4,
                'comment' => '対話形式なので読みやすく、アドラー心理学の考え方を理解しやすかったです。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784478025819',
                'rating' => 5,
                'comment' => '他人の評価を気にしすぎないという考え方が特に印象に残りました。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784163902302',
                'rating' => 5,
                'comment' => '芸人として夢を追う登場人物たちの姿が印象的で、引き込まれました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784163902302',
                'rating' => 5,
                'comment' => '理想と現実の間で悩む人物たちの描写がリアルで心に残りました。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784163902302',
                'rating' => 3,
                'comment' => '芸人の世界を題材にしながら、人の生き方について考えさせられる作品でした。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784822289607',
                'rating' => 4,
                'comment' => '数字やデータをもとに世界を見ることの大切さを学べました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784822289607',
                'rating' => 3,
                'comment' => '自分が思い込みで物事を判断していることに気づかされる内容でした。',
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784822289607',
                'rating' => 5,
                'comment' => '具体的なデータが多く、世界に対する見方が少し変わりました。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784822251468',
                'rating' => 4,
                'comment' => 'コンテナが世界の物流を大きく変えた経緯が分かり、とても興味深かったです。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784822251468',
                'rating' => 5,
                'comment' => '身近に見かけるコンテナが世界経済に与えた影響の大きさに驚きました。',
            ],
        ];
        foreach ($reviews as $reviewData) {
            $user = User::where('email', $reviewData['user_email'])->first();

            $book = Book::where('isbn', $reviewData['book_isbn'])->first();

            Review::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
            ]);
        }
    }
}
