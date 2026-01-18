<?php

return [
    'title' => '論題カタログ',
    'select_btn' => '論題を提案',
    'filter_all' => 'すべて',
    'tab_suggestion' => 'おすすめ',
    'tab_catalog' => 'カタログ',
    'refresh_suggestion' => '別の提案を見る',
    'switch_to_catalog' => 'カタログから選ぶ',
    'switch_to_suggestion' => '提案に戻る',
    'suggestion_title' => 'こちらの論題はいかがですか？',

    'categories' => [
        'politics' => '政治・社会',
        'business' => 'ビジネス・経済',
        'technology' => 'テクノロジー・科学',
        'education' => '教育・学校',
        'philosophy' => '倫理・哲学',
        'entertainment' => 'エンタメ・趣味',
        'lifestyle' => 'ライフスタイル・恋愛',
        'other' => 'その他',
    ],

    'difficulties' => [
    'easy' => 'Easy',
    'normal' => 'Normal',
    'hard' => 'Hard',
    ],

    // ### 1. Easy（初級・入門）
    // **「日常的で、直感や個人の価値観に基づいて話せるテーマ」**

    // *   **定義:**
    //     *   **知識:** 不要。自分の経験や生活感覚だけで議論が可能。
    //     *   **内容:** 「好き嫌い」や「身近な生活習慣」に関するもの。
    //     *   **準備:** 即興で対応可能（リサーチ不要）。
    // *   **ターゲット:** ディベート未経験者、子供、アイスブレイク、飲み会のネタ。
    // *   **具体例:**
    //     *   「犬と猫、飼うならどっち？」
    //     *   「目玉焼きには醤油か、ソースか？」
    //     *   「一生夏と一生冬、どっちが良い？」
    //     *   「お金と愛、どちらが大切か？」

    // ### 2. Normal（中級・標準）
    // **「一般的な社会問題で、論理的な構成力が求められるテーマ」**

    // *   **定義:**
    //     *   **知識:** 一般常識レベル（ニュースで聞く程度）の知識が必要。
    //     *   **内容:** 賛否両論がはっきりしている「王道の社会問題」や「校則・社則」など。
    //     *   **準備:** 少し考える時間は必要だが、専門的なデータがなくても論理（ロジック）で戦える。
    // *   **ターゲット:** 高校生、大学生、論理的思考を鍛えたい社会人。
    // *   **具体例:**
    //     *   「政府は救急車を有料化すべきである」
    //     *   「中学校の制服を廃止すべきである」
    //     *   「死刑制度を廃止すべきである」
    //     *   「飲食店は全面禁煙にすべきである」

    // ### 3. Hard（上級・専門）
    // **「専門知識、または高度な抽象的思考が必要な複雑なテーマ」**

    // *   **定義:**
    //     *   **知識:** 政治、経済、国際情勢、科学技術などの専門知識やリサーチ（証拠資料）が必須。
    //     *   **内容:** 複数の利害関係者が絡む複雑な政策、または答えのない哲学的・倫理的難題。
    //     *   **準備:** 事前の調査や、深い背景理解がないと議論が成立しない。
    // *   **ターゲット:** 競技ディベーター、専門家、知的好奇心が非常に高い層。
    // *   **具体例:**
    //     *   「政府はベーシックインカムを導入すべきである」
    //     *   「デザイナーベビー（遺伝子操作）を認めるべきである」
    //     *   「国連の安全保障理事会の拒否権を廃止すべきである」
    //     *   「（抽象題）自由は平等よりも優先されるべきである」


    // [Category Key, Difficulty Key, Topic Text]
    'topics' => [
    // 1. 政治・社会 (Politics)
    ['category' => 'politics', 'difficulty' => 'easy', 'text' => '街中のゴミ箱をもっと増やすべきである'],
    ['category' => 'politics', 'difficulty' => 'easy', 'text' => 'エスカレーターでの歩行を禁止すべきである'],
    ['category' => 'politics', 'difficulty' => 'easy', 'text' => '「歩きスマホ」を法律で罰金化すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、国政選挙の投票を義務化すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、公共の場での喫煙を全面的に禁止すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、選挙運動におけるインターネット広告を禁止すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、ヘイトスピーチを法的に規制すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、死刑制度を廃止すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、サマータイム制度を導入すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、救急車の利用を有料化すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、プラスチック製レジ袋の製造および販売を禁止すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、動物園および水族館を廃止すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、選択的夫婦別姓制度を導入すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、チケットの転売を全面的に禁止すべきである'],
    ['category' => 'politics', 'difficulty' => 'normal', 'text' => '政府は、ペットの生体販売を禁止すべきである'],
    ['category' => 'politics', 'difficulty' => 'hard', 'text' => '政府は、移民・外国人労働者の受け入れを大幅に拡大すべきである'],
    ['category' => 'politics', 'difficulty' => 'hard', 'text' => '政府は、原子力発電所を全面的に廃止すべきである'],
    ['category' => 'politics', 'difficulty' => 'hard', 'text' => '政府は、未来世代の権利を代弁する公的機関を設置すべきである'],
    ['category' => 'politics', 'difficulty' => 'hard', 'text' => '政府は、積極的安楽死を法的に認めるべきである'],

    // 2. ビジネス・経済 (Business)
    ['category' => 'business', 'difficulty' => 'easy', 'text' => '新卒で入社するなら、ベンチャー企業よりも大企業を選ぶべきである'],
    ['category' => 'business', 'difficulty' => 'easy', 'text' => '仕事選びにおいて、給料よりも「やりがい」を優先すべきである'],
    ['category' => 'business', 'difficulty' => 'easy', 'text' => '20代のうちは、貯金するよりも経験や娯楽にお金を使うべきである'],
    ['category' => 'business', 'difficulty' => 'normal', 'text' => '企業は週休3日制を導入すべきである'],
    ['category' => 'business', 'difficulty' => 'normal', 'text' => '政府は、コンビニエンスストアの深夜営業（24時間営業）を禁止すべきである'],
    ['category' => 'business', 'difficulty' => 'normal', 'text' => '企業は、原則としてリモートワークを導入すべきである'],
    ['category' => 'business', 'difficulty' => 'hard', 'text' => '政府は、消費税を廃止すべきである'],
    ['category' => 'business', 'difficulty' => 'hard', 'text' => '政府は、ベーシックインカムを導入すべきである'],
    ['category' => 'business', 'difficulty' => 'hard', 'text' => '政府は、相続税を100％にすべきである'],
    ['category' => 'business', 'difficulty' => 'hard', 'text' => '政府は、キャッシュレス決済の手数料に上限を設けるべきである'],
    ['category' => 'business', 'difficulty' => 'hard', 'text' => '政府は、自由貿易よりも国内産業の保護を優先すべきである'],
    ['category' => 'business', 'difficulty' => 'hard', 'text' => '企業は、株主利益よりもESG（環境・社会・ガバナンス）を優先すべきである'],

    // 3. テクノロジー・科学 (Technology)
    ['category' => 'technology', 'difficulty' => 'easy', 'text' => '悩み相談をするなら、友人よりもAIの方が良い'],
    ['category' => 'technology', 'difficulty' => 'easy', 'text' => '本を読むなら、電子書籍より紙の本がいい'],
    ['category' => 'technology', 'difficulty' => 'easy', 'text' => '買い物は、現金よりキャッシュレス決済をメインにすべきだ'],
    ['category' => 'technology', 'difficulty' => 'normal', 'text' => '企業の採用面接は、人間ではなくAIが判定すべきである'],
    ['category' => 'technology', 'difficulty' => 'normal', 'text' => 'SNS事業者は、全ユーザーの年齢確認（身分証提示）を義務化すべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '政府は、生成AIによって作成されたコンテンツへのラベル表示を義務化すべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '政府は、一般消費者向けの遺伝子検査サービスの提供を規制すべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '政府は、高度なAIモデルの一般公開を安全保障上の理由から制限すべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '政府は、AIの開発を厳格に規制すべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '政府は、AIによる著作物の学習および模倣を禁止すべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '自動運転車による事故の法的責任は、原則としてメーカーが負うべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '政府は、ヒト受精卵（胚）のゲノム編集を臨床利用目的で認めるべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '政府は、遺伝子操作によるデザイナーベビーの誕生を認めるべきである'],
    ['category' => 'technology', 'difficulty' => 'hard', 'text' => '社会は、出生前診断の利用を推奨すべきである'],

    // 4. 教育・学校 (Education)
    ['category' => 'education', 'difficulty' => 'easy', 'text' => '学校の昼食は、お弁当より給食の方が良い'],
    ['category' => 'education', 'difficulty' => 'easy', 'text' => '小学校でのシャープペンシル使用を解禁すべきだ'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '政府は、子供（18歳未満）のスマートフォン利用時間を法的に制限すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '政府は、義務教育における「デジタル教科書」の本格導入を中止し、紙の教科書を主軸に戻すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '政府は、学校の制服を廃止すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '政府は、英語教育を小学校1年生から義務化すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '政府は、高校の授業料を所得制限なく無償化すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '政府は、9月入学制度を導入すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '学校教育において、宿題への生成AIツールの利用を認めるべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '学校教育において、宿題を廃止すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '学校教育において、飛び級制度を導入すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '公立中学校における部活動は、原則廃止（または地域移行）すべきである'],
    ['category' => 'education', 'difficulty' => 'normal', 'text' => '大学入試において、学力試験よりも人物評価（総合型選抜）を重視すべきである'],
    ['category' => 'education', 'difficulty' => 'hard', 'text' => '政府は、教育バウチャー制度を導入すべきである'],
    ['category' => 'education', 'difficulty' => 'hard', 'text' => '政府は、公立小中学校において習熟度別クラス編成（トラッキング）を全面的に導入すべきである'],
    ['category' => 'education', 'difficulty' => 'hard', 'text' => '政府は、教科書検定制度を廃止すべきである'],
    ['category' => 'education', 'difficulty' => 'hard', 'text' => '政府は、義務教育におけるホームスクーリングを法的に認めるべきである'],

    // 5. 倫理・哲学 (Philosophy)
    ['category' => 'philosophy', 'difficulty' => 'easy', 'text' => '「善意の嘘」は正当化されるべきである'],
    ['category' => 'philosophy', 'difficulty' => 'easy', 'text' => '人生において、お金よりも時間の方が重要である'],
    ['category' => 'philosophy', 'difficulty' => 'easy', 'text' => '成功において、才能よりも努力の方が重要である'],
    ['category' => 'philosophy', 'difficulty' => 'normal', 'text' => '結果が良ければ手段は正当化されるべきである'],
    ['category' => 'philosophy', 'difficulty' => 'normal', 'text' => '芸術作品の評価において、作者の人格や素行は切り離して考えるべきである'],
    ['category' => 'philosophy', 'difficulty' => 'normal', 'text' => '人間は、動物を食べることを倫理的にやめるべきである'],
    ['category' => 'philosophy', 'difficulty' => 'normal', 'text' => '治安維持のためなら、個人のプライバシーは犠牲にされてもよい'],
    ['category' => 'philosophy', 'difficulty' => 'normal', 'text' => '個人的な復讐は、道徳的に正当化される場合がある'],
    ['category' => 'philosophy', 'difficulty' => 'hard', 'text' => '「完全に幸福な幻想」の中で一生を終えることは、苦痛を伴う現実を生きるよりも望ましい'],
    ['category' => 'philosophy', 'difficulty' => 'hard', 'text' => '能力主義（メリトクラシー）に基づく格差は、遺伝的運による差別であり、是正されるべきである'],
    ['category' => 'philosophy', 'difficulty' => 'hard', 'text' => '人間が科学技術を用いて、身体や知能を生物学的限界を超えて強化（エンハンスメント）することは推奨されるべきである'],
    ['category' => 'philosophy', 'difficulty' => 'hard', 'text' => '政治的意思決定権は、平等な一人一票ではなく、知識や判断力に応じた「知識人統治」に移行すべきである'],

    // 6. エンタメ・趣味 (Entertainment)
    ['category' => 'entertainment', 'difficulty' => 'easy', 'text' => 'きのこの山よりもたけのこの里の方が優れている'],
    ['category' => 'entertainment', 'difficulty' => 'easy', 'text' => '映画は自宅（配信）よりも映画館で観るべきである'],
    ['category' => 'entertainment', 'difficulty' => 'normal', 'text' => '未成年のオンラインゲームへの課金は法的に禁止すべきである'],
    ['category' => 'entertainment', 'difficulty' => 'normal', 'text' => 'プロスポーツにおいて、ビデオ判定を全面的に導入すべきである'],
    ['category' => 'entertainment', 'difficulty' => 'normal', 'text' => '著作権法は、二次創作に対してもっと寛容であるべきである'],
    ['category' => 'entertainment', 'difficulty' => 'normal', 'text' => '「推し活」への投げ銭には、法的な上限規制を設けるべきである'],
    ['category' => 'entertainment', 'difficulty' => 'normal', 'text' => 'AIが生成した作品のコンテスト応募は認められるべきである'],
    ['category' => 'entertainment', 'difficulty' => 'hard', 'text' => '文化芸術への公的支援は、人気（大衆性）よりも多様性（芸術性）を優先すべきである'],

    // 7. ライフスタイル・恋愛 (Lifestyle)
    ['category' => 'lifestyle', 'difficulty' => 'easy', 'text' => '結婚前の同棲は推奨されるべきである'],
    ['category' => 'lifestyle', 'difficulty' => 'easy', 'text' => '友人の恋人と二人きりで会うことは、倫理的に非難されるべきである'],
    ['category' => 'lifestyle', 'difficulty' => 'easy', 'text' => '家庭内の家事分担は、効率よりも平等を優先すべきである'],
    ['category' => 'lifestyle', 'difficulty' => 'easy', 'text' => '恋人のスマートフォンを無断で確認することは許されるべきである'],
    ['category' => 'lifestyle', 'difficulty' => 'easy', 'text' => '結婚相手を選ぶ際、性格よりも経済力を重視することは正当化される'],
    ['category' => 'lifestyle', 'difficulty' => 'normal', 'text' => '「子供が3歳になるまでは家庭で育てるべき」という考え（3歳児神話）は否定されるべきである'],
    ['category' => 'lifestyle', 'difficulty' => 'normal', 'text' => '親は、子供の写真をSNSに公開することを控えるべきである'],
    ],

    // AI機能関連
    'ai' => [
        'tab_title' => 'AIに相談',
        'section_title' => 'AIが論題を提案します',
        'section_description' => 'キーワードやテーマを入力すると、AIがディベートに適した論題を生成します。',
        'keywords_label' => 'キーワード・テーマ（任意）',
        'keywords_placeholder' => '例: AI、教育、環境問題...',
        'category_label' => 'カテゴリ（任意）',
        'difficulty_label' => '難易度（任意）',
        'generate_btn' => '論題を生成',
        'generating' => '生成中...',
        'results_title' => '生成された論題',
        'no_results' => '論題が生成されませんでした。別のキーワードをお試しください。',
        'try_again' => '再生成',
        'select_topic' => 'この論題を選択',
        'rate_limit_exceeded' => 'リクエスト回数の上限に達しました。しばらくお待ちください。',
        'generation_failed' => '論題の生成に失敗しました。もう一度お試しください。',
        'base_topic_required' => '分析する論題を入力してください。',
        'error_model_unavailable' => '現在AIモデルが利用できません。しばらく経ってから再度お試しください。',
        'error_bad_request' => 'リクエストに問題がありました。入力内容を確認してください。',
        'error_auth' => 'AI サービスの認証に失敗しました。管理者にお問い合わせください。',
        'error_server' => 'AI サービスが一時的に利用できません。しばらく経ってから再度お試しください。',

        // 背景情報機能
        'info_title' => '論題の背景情報',
        'info_description' => '論題についての解説や論点を確認できます。',
        'get_info_btn' => '背景情報を取得',
        'getting_info' => '取得中...',
        'info_topic' => '論題',
        'info_description_label' => '解説',
        'info_affirmative_points' => '肯定側の論点',
        'info_negative_points' => '否定側の論点',


        // モード切替
        'mode_generate' => '新規生成',
        'mode_info' => '背景情報',
        'btn_insight' => 'AI分析・背景',
        'btn_insight_short' => '分析',
        'btn_suggestion_short' => '提案',
        'analyze_btn' => '分析する',
        'caution' => 'AI生成の結果は必ずしも正確ではありません。参考情報としてご利用ください。',
    ],
];
