<?php
$token = '8392241634:AAEhuPq7YEXiHvJqnhdibQN7Od6-nI3myII';
$chatCollectorID = '-4979425765';

define('TOKEN', $token);
define('BASE_URL', 'https://api.telegram.org/bot' . TOKEN . '/');

$data = json_decode(file_get_contents('php://input'), TRUE);

$chat_id = $data['message']['chat']['id'];
$callback_query = $data['callback_query'];
$callback_id = $callback_query['message']['chat']['id'];
$callback_message_id = $callback_query['message']['message_id'];

$data = $data['callback_query'] ? $data['callback_query'] : $data['message'];
file_put_contents(__DIR__ . '/input.txt', print_r($data, 1)."\n", FILE_APPEND);

$callback_data = $data['data'];

$message = mb_strtolower(($data['text'] ? $data['text'] : $data['data']),'utf-8');

$user = $data['from'];
$user_id = $user['id'];

$chatCollector = $chatCollectorID;

// User state management. Start
function setUserState($user_id, $state) {
    file_put_contents("state_$user_id.txt", $state);
}
function getUserState($user_id) {
    $file = "state_$user_id.txt";
    return file_exists($file) ? file_get_contents($file) : null;
}
function clearUserState($user_id) {
    $file = "state_$user_id.txt";
    if (file_exists($file)) unlink($file);
}
// User state management. End

// Step 1: user clicks menu button
if ($message === '/feedback' || $message === 'оставить отзыв') {
    sendTelegram('sendMessage', [
            'chat_id' => $chat_id,
            'text' => 'Расскажите о ваших впечатлениях от аудиоспектакля и прогулки по усадьбе Строгановых в Волышово. Ваш отзыв поможет нам стать лучше!',
            'reply_markup' => [
                    'remove_keyboard' => true
                ]
        ]);
    setUserState($user_id, 'awaiting_feedback');
}
// Step 2: awaiting feedback (any message type)
elseif (getUserState($user_id) === 'awaiting_feedback') {
    // Send thanks to user
    sendTelegram('sendMessage', [
        'chat_id' => $chat_id,
        'text' => 'Спасибо за ваш отзыв! Мы очень ценим ваше мнение.',
    ]);

    // Build user info block
    $info = "📩 Новый отзыв: $message\n" .
            "👤 Name: " . $user['first_name'] . ' ' . $user['last_name'] . "\n" .
            "🔗 Username: @" . ($user['username'] ?: "N/A") . "\n" .
            "🆔 User ID: $user_id\n";

    // Send info to admin
    sendTelegram('sendMessage', [
        'chat_id' => $chatCollectorID, // ADMIN ID
        'text' => $info
    ]);

    // Forward the original message (text, photo, video, voice, etc.)
    sendTelegram('forwardMessage', [
        'chat_id' => $chatCollectorID,          // ADMIN ID
        'from_chat_id' => $chat_id,   // USER CHAT
        'message_id' => $update['message']['message_id']
    ]);

    // Clear state
    clearUserState($user_id);
}
else {
    switch ($message) {
        case '/start':
            $visitor = "👤 Новый посетитель: " . $user['first_name'] . ' ' . $user['last_name'] . "\n" .
            "🔗 Username: @" . ($user['username'] ?: "N/A") . "\n" .
            "🆔 User ID: $user_id\n";

            sendTelegram('sendMessage', [
                'chat_id' => $chatCollectorID,
                'text' => $visitor
            ]);
            
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $user['first_name'] . " " . $user['last_name'] . " добро пожаловать в усадьбу Строгановых в Волышове!\nМы здесь всегда рады гостям.\nВы можете слушать истории в любом порядке, но мы советуем сохранить режиссёрскую последовательность — Г.П. Пеший, Т. Д. Строганова, К. И. Пейдж, С. А. Строганов, Г. В. Проскурякова. Она строит синусоиду — от сформированной энергии усадебного уклада к его крушению и тихому, но упорному желанию быть услышанным снова.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'управляющий охотой Григорий Павлович Пеший'],
                        ],
                        [
                            ['text' => 'графиня Татьяна Дмитриевна Строганова'],
                        ],
                        [
                            ['text' => 'управляющий конюшнями Карл Иванович Пейдж'],
                        ],
                        [
                            ['text' => 'граф Сергей Александрович Строганов'],
                        ],
                        [
                            ['text' => 'историк-краевед Галина Васильевна Проскурякова'],
                        ]
                    ]
                ]
            ]);

            /*file_get_contents(BASE_URL . $method . '?chat_id=' . $chatCollector . '&text=@' . $user['username'] . ' ' . $user['first_name'] . ' ' . $user['last_name'] . ' ' . $user_id);
            $method = 'sendMessage';
            $send_data = [
                'chat_id' => $chat_id,
                'text' => $user['first_name'] . " " . $user['last_name'] . " добро пожаловать в усадьбу Строгановых в Волышове!\nМы здесь всегда рады гостям.\nВы можете слушать истории в любом порядке, но мы советуем сохранить режиссёрскую последовательность — Г.П. Пеший, Т. Д. Строганова, К. И. Пейдж, С. А. Строганов, Г. В. Проскурякова. Она строит синусоиду — от сформированной энергии усадебного уклада к его крушению и тихому, но упорному желанию быть услышанным снова.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'управляющий охотой Григорий Павлович Пеший'],
                        ],
                        [
                            ['text' => 'графиня Татьяна Дмитриевна Строганова'],
                        ],
                        [
                            ['text' => 'управляющий конюшнями Карл Иванович Пейдж'],
                        ],
                        [
                            ['text' => 'граф Сергей Александрович Строганов'],
                        ],
                        [
                            ['text' => 'историк-краевед Галина Васильевна Проскурякова'],
                        ]
                    ]
                ]
            ];*/
            break;
        
        case '/routs':
        case 'выбрать маршрут':
        case 'выбрать другого героя':
            $method = 'sendMessage';
            $send_data = [
                'chat_id' => $chat_id,
                'text' => "С кем вы хотите отправиться на прогулку?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'управляющий охотой Григорий Павлович Пеший'],
                        ],
                        [
                            ['text' => 'графиня Татьяна Дмитриевна Строганова'],
                        ],
                        [
                            ['text' => 'управляющий конюшнями Карл Иванович Пейдж'],
                        ],
                        [
                            ['text' => 'граф Сергей Александрович Строганов'],
                        ],
                        [
                            ['text' => 'историк-краевед Галина Васильевна Проскурякова'],
                        ]
                    ]
                ]
            ];
            break;

        case '/about':
        case 'о спектакле':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "🎙️ <b>Аудио моноспектакль «Голоса Усадьбы: прогулки сквозь время.»</b>\n\n🧩 <b>Моносаунд:</b> можно слушать в одном наушнике, не теряя контакт с пространством.\n\n<b>Пять маршрутов - Пять глав.</b>\n\n🚩Маршруты независимы, вы можете выбрать любого персонажа или прослушать спектакль в предложенном нами порядке, чтобы получить целостную картину. Каждый маршрут заканчивается «точкой продолжения» — местом, где можно сделать паузу, обсудить впечатления и приступить к следующей главе.💬",
                'parse_mode' => 'html',
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "📣 <b>Как звучит история, если к ней прислушаться?</b>\n\nВы ступаете на гравий парковой аллеи, слышится отзвук шагов – звук самого времени. Ветер приносит запахи с парковых аллей, а в наушнике слышится шёпот: «Позвольте показать вам …». <b>Так Волышово начинает диалог с вами, приглашая стать гостем нескольких эпох.</b> Так начинаются «Голоса Усадьбы» — спектакль из пяти самостоятельных аудионовелл, которые распахивают время и складываются в единый панорамный рассказ о жизни усадьбы. Это не экскурсия в ее привычном для нас понимании, а встреча с живыми голосами прошлого, превращающими вашу прогулку по усадьбе в личную встречу с одним из её прежних обитателей, с его памятью, которая ведет вас за руку.\n\n<b>Здесь нет экскурсовода-лектора, здесь говорят свидетели</b>, а звуки помогают вам видеть прошлое сквозь настоящее. Каждый персонаж — житель Волышово, который приглашает вас в гости, чтобы прогуляться по имению, показать его вам своими глазами и поделиться историей, которую он знает — потому что жил в ней. Персонаж встречает вас, предлагает пройтись по его любимым местам — и незаметно вплетает в прогулку всё то, что делает усадьбу не просто архитектурным памятником, а живой тканью времени. В этом путешествии вы не слушатель, а гость и собеседник.\n\nМы хотим вернуть эмоциональную плотность историческим датам, поэтому <b>сознательно отказываемся от «всемогущего» повествователя</b> и передаем слово самим обитателям усадьбы. <b>У каждого героя своя личная оптика, социальное положение и взгляд на жизнь имения - вместе они образуют полифонию.</b> Каждый герой говорит на языке своего времени, а <b>музыка, написанная специально для каждого персонажа</b>, — не просто фон, а <b>полноправный партнёр</b>, который подчеркивает интонации, раскрывает характеры и сопровождает свою историю.\n\nПо драматургии спектакль напоминает раскрытый веер: каждая пластина автономна. И всё же красота общего рисунка ощущается, только когда веер распахнут до конца. Можно слушать истории в любом порядке, но <b>мы советуем сохранить режиссёрскую последовательность</b>: она строит синусоиду — от сформированной энергии усадебного уклада к его крушению и тихому, но упорному желанию быть услышанным снова.\n\nВ этом, пожалуй, <b>главный смысл всего – дать голос истории места, а вместе с этим и возможности на ее продолжение.</b>  Это не усадьба-музей, а живой организм, переживший смену империй.\n\nПять голосов — одна история.\n\n<b>Слушайте. Вслушивайтесь. Откликайтесь</b>💭",
                'parse_mode' => 'html',
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Проект реализован Фондом «Возрождение усадьбы Строганова в Волышово» в сотрудничестве с РГХПУ им. Строганова.\n\n<b>Куратор проекта:</b> Надежда Ягофарова\n<b>Концепция и драматургия:</b> Дария Раншакова, Екатерина Лаврентьева, Яна Орлова\n<b>Музыка:</b> Тамара Оген\n<b>Звукорежиссура:</b> Олег Лукин\n<b>Разработка:</b> Игорь Вахромеев\n<b>Исполнители:</b>\nГ. П. Пеший — Дмитрий Поляков\nТ. Д. Строганова — Эльвира Насибуллина\nК. И. Пейдж — Георгий Пепеляев\nС. А. Строганов - Илья Гулько\nГ. В. Проскурякова — Юлия Фролова",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'remove_keyboard' => true
                ]
            ]);
            break;

        case '/contacts':
        case 'контакты':
            $method = 'sendMessage';
            $send_data = [
                'chat_id' => $chat_id,
                'text' => "Наши контакты\n📮 <a href='https://t.me/stroganov_estate'>@stroganov_estatebot</a>\n\n📩 <a href='mailto:info@stroganov.estate'>info@stroganov.estate</a>\n\n📍 <a href='https://stroganov.estate'>stroganov.estate</a>\n\n👥 <a href='https://vk.com/stroganov.estate'>VK</a>",
			    'parse_mode' => "html",
                'reply_markup' => [
                    'remove_keyboard' => true
                ]
            ];
            break;

        case '/donation':
        case 'поддержать':
            $method = 'sendMessage';
            $send_data = [
                'chat_id' => $chat_id,
                'text' => "Поддержать проект\n<a href='https://t.me/vozrozhdenie_stroganov_bot'>@vozrozhdenie_stroganov_bot</a>",
			    'parse_mode' => "html",
                'reply_markup' => [
                    'remove_keyboard' => true
                ]
            ];
            break;

        // Григорий Павлович Пеший. Начало
        case '/peshiy':
        case 'управляющий охотой григорий павлович пеший':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy.png',
                'caption' => "Пеший Георгий Павлович\nпредположительно 1860 – 1940 гг.",
                'parse_mode' => 'html',
                
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/prolog G.P. Peshij.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Управляющий «арабской» или, как ее еще называют, «казачьей» конюшней и охотой. Располагал особым штатом охотников, кучером, конюхами и самостоятельным хозяйством на окраине села. В его ведение также была отдана домовая церковь, находившаяся рядом с графским домом. Благодаря этой должности он пользовался особым авторитетом в округе и уважением у местных крестьян.\nДо отмены крепостного права его семья относилась к крепостной интеллигенции. Был человеком семейным, образованным, в молодости был прекрасным наездником и охотником, а в зрелые годы не только хорошим организатором порученного дела, но и лучшим игроком в крикет и крокет.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Карта маршрута Григория Павловича'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'карта маршрута григория павловича':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-rout.jpg',
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-icon.jpg',
                'caption' => "Год вашей встречи в аудиоспектакле — 1890. Маршрут выделен пиктограммой «Охота».",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Начать маршрут Григория Павловича'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'начать маршрут григория павловича':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Добро пожаловать к нам в Волышово. Позвольте показать вам, как здесь все устроено?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, начните, пожалуйста, ваш рассказ']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        
        case 'да, начните, пожалуйста, ваш рассказ':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Отлично! Пока я начинаю свой рассказ о Волышове, давайте прогуляемся с вами мимо Дома управляющего до нашего Храма, он будет по правую руку от нас.",
                'parse_mode' => 'html'
            ]);
            
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/Selo Volyshovo.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Звучит захватывающе, расскажите ещё']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);

            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-01-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-01-02.png',
                    ]
                ])
            ]);
            
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-timeline-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-timeline-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-timeline-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-timeline-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-timeline-05.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-timeline-06.jpg',
                    ]
                ])
            ]);
            break;

        case 'звучит захватывающе, расскажите ещё':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Вот мы и дошли до нашего храма. Пока мы стоим тут, позвольте рассказать о его архитектуре?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Конечно, жду с нетерпением!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'конечно, жду с нетерпением!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/Ob arhitekture hrama.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Не останавливайтесь на этом месте, расскажите больше']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);

            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-02-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-02-02.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-02-03.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-02-04.png',
                    ]
                ])
            ]);
            break;

        case 'не останавливайтесь на этом месте, расскажите больше':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Предлагаю продолжить наш путь в сторону парадных Красных ворот по этой аллее. А пока мы идем, я продолжу свой рассказ о нашем Храме.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, продолжайте, пожалуйста! Мне нравится, как вы рассказываете']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        
        case 'да, продолжайте, пожалуйста! мне нравится, как вы рассказываете':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/O funkcii volyshovskoj cerkvi.mp3",
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-03-01.png',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Мне нравится, как вы рассказываете, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'мне нравится, как вы рассказываете, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "И вот мы наконец дошли до ворот парадного въезда. О, а сколько гостей проезжает через них, когда начинается охота! Позвольте я вам расскажу о ней!",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Конечно, не терпится услышать!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'конечно, не терпится услышать!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Как я рад, что вас заинтересовала эта тема. Давайте свернём на парковую тропинку — пока мы идём в сторону хозяйственной зоны усадьбы, связанной с охотой, я вам все подробно расскажу.",
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/Ob ohote.mp3",
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-04-01.png',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Пожалуйста, не прерывайтесь, хочется узнать больше']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'пожалуйста, не прерывайтесь, хочется узнать больше':  
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Хотите я поподробнее расскажу о традициях волышовской охоты?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Интригующе, жду с нетерпением!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'интригующе, жду с нетерпением!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Отлично, тогда продолжаем наш путь, а я — свой рассказ!",
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/O tradicii volyshovskoj ohoty.mp3",
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-05-01.png',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Звучит захватывающе, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'звучит захватывающе, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "И вот мы вышли из усадебного парка и продолжим нашу прогулку в сторону Псарника и Охотничьей конюшни. Но смею предположить, вы, вероятно, уже частично сложили нашу семейную историю и то, как я получил пост управляющего… Но я бы хотел сам рассказать об этом вам.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Не прерывайтесь, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        
        case 'не прерывайтесь, продолжайте':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/O tragedii sestry i o tom kak ya poluchil post upravlyayushego.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Не прерывайтесь на этом, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-06-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-06-02.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-06-03.png',
                    ]
                ])
            ]);
            break;

        case 'не прерывайтесь на этом, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Но хватит рассказов обо мне. Мы пришли в сердце охотничьей жизни усадьбы. Позвольте я все здесь вам покажу. Перед нами Псарник, а за ней конюшни. Не против, если прогуляемся вдоль этих зданий, пока я рассказываю о них?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Конечно, давайте пройдем, а я послушаю!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'конечно, давайте пройдем, а я послушаю!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/Pro arhitekturu psarni.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Не останавливайтесь на этом, расскажите еще']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-07-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-07-02.png',
                    ]
                ])
            ]);
            break;

        case 'не останавливайтесь на этом, расскажите еще':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Теперь давайте прогуляемся к другим конюшням, где держат рабочих лошадей. А я расскажу вам о том, как мы готовимся к охоте.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Очень увлекательно, продолжайте!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'очень увлекательно, продолжайте!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/O podgotovke k ohote.mp3",
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-08-01.png',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Очень увлекательно Георгий Павлович, продолжайте!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'очень увлекательно георгий павлович, продолжайте!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Наша прогулка завершается. Предлагаю вернуться к Главным конюшням. А пока мы идем, я, с вашего позволения, поделюсь с вами воспоминаниями о моей семье и юности, которую я провел подле нашего графа.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, жду с нетерпением эти истории']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, жду с нетерпением эти истории':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/O seme.mp3",
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/peshiy-09-01.png',
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/O yunosti obrazovanii i otnosheniyah s grafom S. A. Stroganovym.mp3",
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/peshiy/epilog G.P. Peshij.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "После Февральской революции 1917 года управляющий Герман Христианович Мозерт ушел со своего поста (или был смещен). Тогда Григорий Павлович Пеший занял место управляющего, переехал в его дом (Дом управляющего) и начал «наводить порядок», переделывать волышовское хозяйство по своим давним, по-видимому, планам. Время было совсем не для реформ: крестьяне уже брали в свои руки леса, поля и сенокосы помещиков.\nДеятельность нового управляющего оттолкнула от него не только крестьян, но и жителей села. В итоге ему едва удалось уехать, точнее, бежать из Волышово в Петроград перед самой Октябрьской революцией, оставив все, что было нажито за десятилетия обеспеченной и умеренной жизни.\nБолее осторожный его предшественник дожил свои дни недалеко от Волышово.\nГ. В. Проскурякова. Волышовская старина: О родине  ̶  Псковском крае. (Из воспоминаний), Спб.: ООО «СРП «Павел» ВОГ», 2008, - 55 с.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Завершить прогулку с Григорием Павловичем']
                        ]
                    ]
                ]
            ]);
            break;

        case 'завершить прогулку с григорием павловичем':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Спасибо за нашу прогулку и до новых встреч здесь, в усадьбе Строгановых в Волышове. Если хотите, Вы можете продолжить прогулку с Татьяной Дмитриевной Строгановой или вернуться позже.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжить прогулку с Т. Д. Строгановой']
                        ],
                        [
                            ['text' => 'Выбрать другого героя']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        // Григорий Павлович Пеший. Конец

        // графиня Татьяна Дмитриевна Строганова. Начало
        case '/stroganova':
        case 'графиня татьяна дмитриевна строганова':
        case 'продолжить прогулку с т. д. строгановой':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova.png',
                'caption' => "Графиня Строганова Татьяна Дмитриевна, урожд. Васильчикова\n19.03.1823 - 16.10.1880",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Карта маршрута Татьяны Дмитриевны'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/prolog T.D. Stroganova.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<b>19.03.1823 - 16.10.1880</b>\nЖена графа Александра Сергеевича Строганова (1818 – 1864), полковника, флигель-адъютанта, действительного статского советника, егермейстера. Младшая дочь генерала от кавалерии, егермейстера Дмитрия Васильевича Васильчикова (1778 – 1859) и Аделаиды Петровны, урожд. графини Апраксиной (ок. 1785 – 1851). Фрейлина Высочайшего Двора.  Внесла немалый вклад в сохранение уклада и архитектуры Волышово.\n\nГраф Сергей Дмитриевич Шереметев так её охарактеризовал: <i>«У неё было много приятелей и поклонников, статная и большого роста, она казалась выточенной из блестящей стали. В ней было русского — только её имя. Она оберегала всю жизнь своё достоинство, и жизнь свою повела по заученному виллийскому уроку. Она принадлежала к числу металлических женщин.»</i>",
                'parse_mode' => 'html',
            ]);
            break;

        case 'карта маршрута татьяны дмитриевны':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-rout.jpg',
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-icon.jpg',
                'caption' => "Год вашей встречи в аудиоспектакле – 1870. Маршрут выделен пиктограммой «Роза»",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Начать маршруту Татьяны Дмитриевны'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'начать маршруту татьяны дмитриевны':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Рада, что вы навестили нас здесь, в этом имении. Должна признаться, что последние годы я с неохотой покидаю его. Эти места полны воспоминаний: каждый уголок дома, каждый поворот аллей в парке хранят память о моем детстве, материнстве и семейной теплоте всех тех дней, что я провела здесь. Позвольте мне небольшую слабость предаться воспоминаниям и поделиться ими с вами?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, Татьяна Дмитриевна, начните, Ваш рассказ']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, татьяна дмитриевна, начните, ваш рассказ':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Что же... В таком случае позвольте предложить вам прогуляться по ивовой аллее в сторону «Красных ворот» и дальше в Дорогини. Отсюда всего около мили до них. А я начну свой рассказ.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Татьяна Дмитриевна, история впечатляет, не останавливайтесь']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/O detyah.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-02-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-02-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-02-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-01-01.jpg',
                    ]
                ])
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/O muzhe.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-03-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-03-02.jpg',
                    ]
                ])
            ]);
            break;

        case 'татьяна дмитриевна, история впечатляет, не останавливайтесь':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "И вот мы в Дорогинях. Это была усадьба наших ближайших соседей по реке Шелони – Чихачевых. В свою бытность мой отец Дмитрий Васильевич выкупил ее с целью развития коневодства и псовой охоты здесь. Тут были выстроены конюшня с манежем, овчарня, склады и жилые дома, а я, в свою очередь, продолжаю поддерживать и развивать это. Возможно, вам будет интересно пройтись по ним, а я подожду Вас тут, чтобы продолжить нашу прогулку.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, продолжайте Ваш рассказ, а в Дорогинях я погуляю позже.']
                        ],
                        [
                            ['text' => 'Спасибо, я прогуляюсь по Дорогиням и вернусь к вам.'],
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'cпасибо, я прогуляюсь по дорогиням и вернусь к вам.':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Конечно, я дождусь вас тут, как будете готовы, дайте знать.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Давайте продолжим прогулку!'],
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'давайте продолжим прогулку!':
        case 'да, продолжайте ваш рассказ, а в дорогинях я погуляю позже.':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Прекрасно! В моих планах прогуляться по аллее обратно до «Красных ворот» и у них свернуть на мою любимую парковую тропинку, а путь наш, я надеюсь, Вам скрасит мой рассказ о том, каков был уклад здешних мест при отце моем, Дмитрии Васильевиче.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Захватывающе, пожалуйста, не прерывайтесь']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/Zhizn i ustrojstvo pri otce D. V. Vasilchikove.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-04-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-04-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-04-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-04-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-04-05.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-04-06.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-04-07.jpg',
                    ]
                ])
            ]);
            break;

        case 'захватывающе, пожалуйста, не прерывайтесь':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "И мы вновь у «Красных ворот», которые нам служат парадным въездом в имение. Давайте мы сейчас свернем налево от них и прогуляемся по парку. И, быть может, после моих рассказов об отце вам будет интересно услышать историю моих предков?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, расскажите о них. И продолжим нашу прогулку']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, расскажите о них. и продолжим нашу прогулку':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/O rode Vasilchikovyh.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Очень увлекательно, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-05-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-05-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-05-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-05-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-05-05.jpg',
                    ]
                ])
            ]);
            break;

        case 'очень увлекательно, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Наша прогулка по парку продолжается. Через пару минут мы выйдем из него в хозяйственную часть усадьбы. Часто, гуляя тут, я думаю о наших людях, о том, как лучше организовать их быт в имении. Осмелюсь предположить, вам будут интересны мои размышления касаемо этого вопроса?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Очень! Начните, пожалуйста, ваш рассказ!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'очень! начните, пожалуйста, ваш рассказ!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/O krestyanah i zhizni imeniya.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Не останавливайтесь на этом, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-06-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-06-02.jpg',
                    ]
                ])
            ]);
            break;

        case 'не останавливайтесь на этом, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Мы вышли в хозяйственную часть нашего имения, и пока мы идем в сторону моста, с которого откроется вид на наш дом, мне бы хотелось поделиться с вами воспоминаниями о том, какие преобразования были здесь за последние двадцать лет после моего замужества.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, расскажите, Татьяна Дмитриевна!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, расскажите, татьяна дмитриевна!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/Ob arhitekture hozyajstvennyh postroek.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Интересно, расскажите еще о усадьбе']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-07-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-07-02.jpg',
                    ]
                ])
            ]);
            break;

        case 'интересно, расскажите еще о усадьбе':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Отсюда открывается прекрасный вид на наш дом, не так ли? Позвольте я вам расскажу о внутреннем убранстве, пока мы прогуливаемся до его парадных лестниц?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Конечно, расскажите!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'конечно, расскажите!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/Interer usadby.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Интересно, жду продолжения!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-08-01.jpg',
            ]);
            break;

        case 'интересно, жду продолжения!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "И вот мы снова вернулись в центральную часть нашего имения, и мне вспомнились те годы, когда я была молода и наблюдала за тем, как мой отец занимался обустройством этих мест… Как все здесь обретало постепенно свои нынешние очертания, сначала отражая ход мысли моего отца, а после замужества и моего супруга.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Расскажите пожалуйста об архитектуре']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'расскажите пожалуйста об архитектуре':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/O arhitekture usadby pri otce D. V. Vasilchikove i muzhe A.S. Stroganove.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжайте, хочется узнать больше о вас']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-09-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-09-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-09-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-09-04.jpg',
                    ]
                ])
            ]);
            break;

        case 'продолжайте, хочется узнать больше о вас':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "У нас получилась долгая прогулка, и, завершая ее, я предлагаю вернуться к Главным конюшням. А я позволю себе поделиться с вами моими самыми сокровенными размышлениями...",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Я жду с нетерпением!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/O sebe.mp3",
            ]);
            break;

        case 'я жду с нетерпением!':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganova-10-01.jpg',
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganova/epilog T.D. Stroganova.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Татьяна Дмитриевна Строганова скончалась в 1880 году, через четыре года после смерти дочери Елены. Последние годы жизни она постоянно проживала в усадьбе Волышово, где и умерла. К 1880-м годам Волышово предстало в своём завершённом зрелом виде, как сложившийся ансамбль, продуманный и в архитектурном, и в духовном смысле: Господский дом, два флигеля, Церковь с жилыми строениями, ухоженная конюшня — всё было на своём месте, всё дышало порядком, достоинством и трудом.\nВ 1923 году, со смертью ее сына, Сергея Александровича Строганова, который не оставил потомства, прервался род графов Строгановых.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Завершить прогулку с Татьяной Дмитриевной']
                        ]
                    ]
                ]
            ]);
            break;

        case 'завершить прогулку с татьяной дмитриевной':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Спасибо за нашу прогулку и до новых встреч здесь, в усадьбе Строгановых в Волышове. Если хотите, Вы можете продолжить прогулку с Карлом Ивановичем Пейджем или вернуться позже.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжить прогулку с К. И. Пейджем']
                        ],
                        [
                            ['text' => 'Выбрать другого героя']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        // графиня Татьяна Дмитриевна Строганова. Конец
        
        // Карл Иванович Пейдж. Начало
        case '/page':
        case 'управляющий конюшнями карл иванович пейдж':
        case 'продолжить прогулку с к. и. пейджем':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/page.png',
                'caption' => "Пейдж Карл Иванович",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Карта маршрута Карла Ивановича'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/page/prolog K.I. Pejdzh.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Управляющий конным заводом. На работу в Волышово поступил в конце 1880-х, стал преемником после брата Вильяма (Василия Ивановича).\nОн лично занимался поставками лошадей из Англии в Волышово, а в усадьбе заведовал разведением лошадей породы гунтер для парфорсной охоты. В имении много времени проводил на работе, сам объезжал лошадей, руководя всем сложным хозяйством конного завода и конюшен.",
                'parse_mode' => 'html'
            ]);
            break;

        case 'карта маршрута карла ивановича':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/page-rout.jpg',
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/page-icon.jpg',
                'caption' => "Год вашей встречи в аудиоспектакле - 1910 г.\nЕго маршрут выделен пиктограммой «Лошади».",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Начать маршрут Карла Ивановича'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'начать маршрут карла ивановича':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Смею предположить, вы наслышаны о наших конюшнях. Возможно, вам интересно, как устроено наше конное хозяйство?",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, расскажите, Карл Иванович!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        
        case 'да, расскажите, карл иванович!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Чудесно! Тогда начинаю свой рассказ, пока мы стоим тут, у Главных конюшен.",
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/page/Konezavod.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Пожалуйста, продолжайте, это интересно']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-01-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-01-02.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-02-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-02-02.png',
                    ]
                ])
            ]);
            break;
        
        case 'пожалуйста, продолжайте, это интересно':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Знаете, я ведь начал свою службу здесь после брата... Не каждый решился бы идти по его стопам! Интересно вам, почему я выбрал это дело и как вообще складываются мои будни здесь?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Пожалуй, это будет интересно!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'пожалуй, это будет интересно!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Хорошо, давайте прогуляемся мимо Главных конюшен в сторону Манежа, и я все вам поведаю!",
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/page/obo mne.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Интересно, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/page-03-01.png',
            ]);
            break;

        case 'интересно, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Но позвольте, а вы ранее видели наш Манеж? Давайте остановимся напротив, и я вам расскажу о нем все, что знаю!",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Еще не видел, расскажите о нем, пожалуйста']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        
        case 'еще не видел, расскажите о нем, пожалуйста':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/page/O manezhe.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Интересный рассказ, продолжайте!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-04-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-04-02.png',
                    ]
                ])
            ]);
            break;
        
        case 'интересный рассказ, продолжайте!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Вы, наверное, догадались, что в нашем поголовье были не просто кони, а тщательно подобранные породы. Хотите узнать, кого мы разводили и почему выбор пал именно на них?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Поведайте!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'поведайте!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Прекрасно, давайте прогуляемся вокруг Главных конюшен, вы сможете оценить весь архитектурный размах здания, а я расскажу вам о лошадях, которые там содержались.",
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/page/O loshadyah.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Впечатляюще, что дальше?']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-05-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-05-02.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-05-03.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-05-04.png',
                    ]
                ])
            ]);
            break;

        case 'впечатляюще, что дальше?':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Ну что же... Вот мы с вами и сделали небольшой круг по центральной части усадьбы, которая связана с коневодством. Возможно, напоследок вам интересно узнать о моих поездках в Англию? Я ведь сам ездил за лошадьми, бывал на лучших заводах, договаривался напрямую. Есть что вспомнить...",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Конечно, расскажи о Ваших поездках']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'конечно, расскажи о ваших поездках':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/page/O poezdkah v Angliyu.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Незадолго до начала Первой Мировой войны, когда в воздухе витали перемены не к добру, Карл Иванович бежал из России, оставив все — налаженное хозяйство, службу и…жену. В Англии его уже ждала вдова его брата с сыновьями. Что стало дальше с Карлом Ивановичем, история умалчивает. Его жена, распродав все, тоже уехала из Дорогинь. А их дом в годы Первой Мировой служил для размещения рабочей дружины. Дом и сейчас стоит, напоминая нам о нехитрых историях, которые таят его стены.\nПосле революции, в 1923 году на базе Графского конезавода в Волышове был основан государственный племенной конезавод, получивший в советском реестре название «Псковский конезавод № 18», он же «Порховский», он же «Волышовский». А многочисленные здания усадьбы стали использовать под жилье, хозяйственные, административные и общественные нужды.\nВ 1941 году ценных породистых лошадей успели эвакуировать до того, как немцы заняли Псков и Порхов. «Эвакуация» растянулась на 1 год и две недели, лошадей гнали своим ходом. Животные погибали, люди от болезней и лишений умирали, но, несмотря ни на что, после оккупации в Волышово вернулись 17 лошадей.\nВ родословных нынешних рысаков записаны предки в восьмом и даже девятом коленах. До 1990-х годов Псковский конезавод входил в пятерку лучших конезаводов СССР.\nГлядя на то, что представляет собой конезавод сейчас, очень сложно представить, что в 1970-80-е гг. волышовские лошади были известны всему миру. Однако это так. Благодаря кропотливой работе коллектива завода во главе с главным зоотехником А.Г. Куницкой удалось получить выносливых рысаков. Псковские лошади проходили испытания и брали призы на восьми ипподромах страны: в Москве, Киеве, Одессе, Таллине, Горьком, Перми, Калинине и Пскове.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Завершить прогулку с Карлом Ивановичем']
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/page/epilog K.I. Pejdzh.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-06-01.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-06-02.png',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/page-06-03.png',
                    ]
                ])
            ]);
            break;

        case 'завершить прогулку с карлом ивановичем':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Спасибо за нашу прогулку и до новых встреч здесь, в усадьбе Строгановых в Волышове. Если хотите, Вы можете продолжить прогулку с Сергеем Александровичем Строгановым или вернуться позже.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжить прогулку с С. А. Строгановым']
                        ],
                        [
                            ['text' => 'Выбрать другого героя']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        // Карл Иванович Пейдж. Конец

        // граф Сергей Александрович Строганов. Начало
        case '/stroganov':    
        case 'граф сергей александрович строганов':
        case 'продолжить прогулку с с. а. строгановым':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov.png',
                'caption' => "Граф Строганов Сергей Александрович\n9.01.1852 - 18.04.1923",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Карта маршрута Сергея Александровича'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/prolog S.A. Stroganov.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Последний представитель знаменитой семьи меценатов, коллекционеров и заводчиков. Капитан 1-го ранга, порховский уездный предводитель дворянства. Внук графа Сергея Григорьевича Строганова, основателя «Школы рисования в отношении к искусствам и ремеслам», которая сейчас носит название Российского государственного художественно-промышленного университета имени С. Г. Строганова. Унаследовал от деда нераздельное имение Строгановых, включавшее заводы и земли на Урале. Основал ныне Терский конный завод на Кавказе (Графский хутор – Змеевка), целью которого было выведение охотничьей лошади.",
                'parse_mode' => 'html'
            ]);
            break;
        
        case 'карта маршрута сергея александровича':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-rout.jpg',
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-icon.jpg',
                'caption' => "Год вашей встречи в аудиоспектакле – 1899. Маршрут выделен пиктограммой «Шишка тсуги».",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Начать маршруту Сергея Александровича'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'начать маршруту сергея александровича':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Добро пожаловать в мое имение, здесь всегда рады гостям! Позвольте предложить вам сделать круг по самому центру моей усадьбы. Мы начнем наш путь от Главной конюшни и завершим здесь же. А пока мы прогуливаемся, хотелось бы поделиться с вами воспоминаниями о детстве и юности.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Расскажите пожалуйста, Сергей Александрович']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'расскажите пожалуйста, сергей александрович':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/O sebe.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Ваша история впечатляет, не останавливайтесь']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-01-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-01-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-01-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-01-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-01-05.jpg',
                    ]
                ])
            ]);
            break;

        case 'ваша история впечатляет, не останавливайтесь':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Смею предположить, вы давно не были в Волышове? Позвольте я проведу вас к моему дому и по пути поделюсь своими размышлениями об укладе здешних мест?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, звучит интригующе!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, звучит интригующе!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/Ob usadbe.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжайте Ваш рассказ, пожалуйста']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-02-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-02-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-02-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-02-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-02-05.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-02-06.jpg',
                    ]
                ])
            ]);
            break;

        case 'продолжайте ваш рассказ, пожалуйста':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Вот мы и подошли к Главному дому. Позвольте рассказать вам немного о тех переменах, что я предпринял, когда стал полноправным хозяином имения?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, мне очень интересно это услышать!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, мне очень интересно это услышать!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/O Volyshovo.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Что случилось потом? Очень интересно']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-03-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-03-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-03-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-03-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-03-05.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-03-06.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-03-07.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-03-08.jpg',
                    ]
                ])
            ]);
            break;

        case 'что случилось потом? очень интересно':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "После моего вступления в права я обнаружил, что наш просторный деревянный дом неумолимо увядал. Давайте обойдем его справа, чтобы вы могли полюбоваться другим его фасадом, и спустимся вниз к мосту. А пока мы гуляем, возможно, вам будет интересно послушать, какие действия были предприняты мною для его перестройки?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, конечно, какие?']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, конечно, какие?':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/O glavnom dome.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Сколько много работы! Продолжайте!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-04-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-04-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-04-03.jpg',
                    ]
                ])
            ]);
            break;

        case 'сколько много работы! продолжайте!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "И вот мы дошли до моста. Предлагаю нам задержаться тут. И раз уж я так подробно поделился с вами историей перестройки нашего Главного дома, позвольте я дополню свой рассказ о его внутреннем убранстве?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, мне интересно, продолжайте!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, мне интересно, продолжайте!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/Ob arhitekture glavnogo doma.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Вас интересно слушать, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-05-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-05-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-05-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-05-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-05-05.jpg',
                    ]
                ])
            ]);
            break;

        case 'вас интересно слушать, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Да-да… Я уже упоминал мою супругу… К сожалению, вам не довелось с ней познакомиться, но позвольте мне слабость поведать вам историю нашего союза?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Очень хочу услышать подробности, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'очень хочу услышать подробности, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Ну что ж… Давайте продолжим нашу прогулку, пройдем через мост и повернем по тропинке направо, а я тем временем начну свой рассказ.",
                'parse_mode' => 'html'
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/O zhene.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Очень интересно, хочется узнать больше']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-06-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-06-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-06-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-06-04.jpg',
                    ]
                ])
            ]);
            break;

        case 'очень интересно, хочется узнать больше':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Довольно обо мне… Сейчас мы находимся в самом сердце хозяйственной части этой усадьбы, давайте задержимся здесь. Отсюда вы видите Псарник, далее за ним располагается Охотничья, или, как мы ее еще называем, Казачья конюшня, а за ней расположилась конюшня Орловская. Через дорогу вы можете наблюдать наш Зверинец. Пока мы здесь отдыхаем от прогулки, желаете, я более подробно расскажу о нашем хозяйственном устройстве?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Любопытно, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'любопытно, продолжайте':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/Hozyajstvennye postrojki usadby.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Любопытно, поделитесь продолжением']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-07-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-07-02.jpg',
                    ]
                ])
            ]);
            break;

        case 'любопытно, поделитесь продолжением':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Что же… Я думаю, мы достаточно отдохнули. Предлагаю продолжить нашу прогулку и пройтись в сторону Рабочих конюшен, а я в это время поделюсь с вами историей моих увлечений.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Звучит захватывающе, расскажите!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'звучит захватывающе, расскажите!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/Uvlechenie konezavod sobakovodstvo.mp3",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжайте, хочется узнать больше']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-08-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-08-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-08-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-08-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-08-05.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-08-06.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-08-07.jpg',
                    ]
                ])
            ]);
            break;

        case 'продолжайте, хочется узнать больше':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Наша прогулка подходит к своему завершению, и я вижу, что в вас созрел вопрос касаемо моего происхождения. Если я прав, позвольте мне удовлетворить ваше любопытство и рассказать историю моего рода?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Очень ценно, что вы делитесь, расскажите!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'очень ценно, что вы делитесь, расскажите!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/O dinastii Sroganovyh.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-09-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-09-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-09-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-09-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-09-05.jpg',
                    ]
                ])
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/stroganov/epilog S.A. Stroganov.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Несмотря на многочисленные письма сестры Марии и ее настоятельные приглашения в Волышово, Сергей Александрович с 1907 года проживал преимущественно в Париже и вернулся в Россию, в Петроград только в 1915…ради долга, но всего на пару месяцев. Революцию он застал на вилле Кап Эстель, которую он приобрёл для своей спутницы, той, что дала надежду на обыкновенное земное счастье, французской дворянки Генриетты Розы Ангелины Левьез. Они связали свои жизни в начале 1900-х, а официально оформили отношения только в 1918 году.\nА в 1919 году Сергей Александрович продал свои юридические права на владение и пользование Пермским нераздельным имением Карлу Иосифовичу Ярошинскому за 14 миллионов французских франков. И последние свои годы он провел на вилле в Эзе близ Ниццы, где умер 18 апреля 1923 года и был похоронен на Русском кладбище Кокад.\n***\nДо 20 апреля 2025 году во Франции жила правнучатая племянница Сергея Александровича - Елена Андреевна Строганова (баронесса Элен де Людингаузен) – правнучка князей Александра и Ольги Щербатовой (сестры Сергея Александровича).\nОна была уникальной женщиной, которая совмещала в себе необыкновенную Строгановскую страсть к искусству и коммерческую жилку. Долгое время баронесса работала в доме высокой моды у Ив Сен-Лорана. Позже она полностью посвятила себя меценатской деятельности, основав фонд, на средства которого в России реставрировались здания, церкви, музеи.\nУ нее не было детей, и она прекрасно понимала, что с ее кончиной история славного Строгановского рода окончательно прервется. Она часто бывала в России, навещая родовые имения, усадьбы, заводы, музеи и храмы. Прикладывала огромные усилия, чтобы сохранить наследие.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Завершить прогулку с Сергеем Александровичем']
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-10-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-10-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/stroganov-10-03.jpg',
                    ]
                ])
            ]);
            break;

        case 'завершить прогулку с сергеем александровичем':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Спасибо за нашу прогулку и до новых встреч здесь, в усадьбе Строгановых в Волышове. Если хотите, Вы можете продолжить прогулку с Галиной Васильевной Проскуряковой или вернуться позже.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжить прогулку с Г. В. Проскуряковой']
                        ],
                        [
                            ['text' => 'Выбрать другого героя']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        // граф Сергей Александрович Строганов. Конец

        // Галина Васильевна Проскурякова. Начало
        case '/proskuryanova':
        case 'историк-краевед галина васильевна проскурякова':
        case 'продолжить прогулку с г. в. проскуряковой':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova.png',
                'caption' => "Проскурякова Галина Васильевна\n25.11.1903–31.12.1992",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Карта маршрута Галины Васильевны'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/prolog G.V.Proskuryakova.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Историк-краевед и педагог. Сыграла значительную роль в развитии псковского краеведения и педагогики. Автор книг: «Шесть экскурсий по Пскову» (1959), «Псковский край в истории СССР» (1970), «Псков. Очерки истории» (1971, 1990). В 1992 году вышла в свет её последняя работа — «О родине — Псковском крае», посвящённая Волышово и Порхову.",
                'parse_mode' => 'html'
            ]);
            break;
        
        case 'карта маршрута галины васильевны':
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-rout.jpg',
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-icon.jpg',
                'caption' => "Год вашей встречи в аудиоспектакле — 1960. Маршрут выделен пиктограммой «Георгин».",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Начать маршрут Галины Васильевны'],
                        ],
                        [
                            ['text' => 'Выбрать другого героя'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'начать маршрут галины васильевны':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Я родилась здесь, в Волышове. Множество моих детских воспоминаний связаны с этим местом, и я рада, что вы сегодня приехали к нам сюда. Предлагаю начать нашу прогулку из самого сердца усадьбы. Возможно, вам было бы интересно узнать, как эти места пережили события первой половины XX века?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Конечно, Галина Васильевна, жду ваш рассказ с огромным интересом!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'конечно, галина васильевна, жду ваш рассказ с огромным интересом!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/O Volyshovo posle oktyabrskoj revolyucii.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-01-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-01-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-01-03.jpg',
                    ]
                ])
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Вся моя жизнь тесно связана с Волышово. Мой дед Эндрю Фишер, англичанин, прадед Павел Калинович и дед по матери Григорий Пеший были управляющими в имении. Я выросла здесь... Скажите, любопытно ли вам узнать, как детство в усадьбе определило всю мою дальнейшую жизнь?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, мне интересно послушать вашу историю']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, мне интересно послушать вашу историю':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "С удовольствием начну свой рассказ! Моё детство — это целый мир, и, если вам действительно интересно, предлагаю прогуляться и сделать круг по центральной части усадьбы, пока вы слушаете мой рассказ.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжайте, Галина Васильевна']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/O sebe.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-02-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-02-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-02-03.jpg',
                    ]
                ])
            ]);
            break;

        case 'продолжайте, галина васильевна':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "И вот мы с вами снова вернулись к зданию Главной конюшни. Предлагаю теперь пройти мимо Дома управляющего, Конторы и Английского домика, в котором в свое время жила моя бабушка с дедом, в сторону Большого манежа и Ремесленной школы. Из моего рассказа о детстве, я думаю, вы уже поняли, что наша семья была очень дружна. Возможно, вам будет интересно послушать о наших семейных прогулках по усадьбе?",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Конечно, это может быть очень любопытно!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        
        case 'конечно, это может быть очень любопытно!':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Да-да, с большим удовольствием расскажу!",
                'parse_mode' => 'html'
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/Progulki po parku s semej.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-03-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-03-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-03-03.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-03-04.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-03-05.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-03-06.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-03-07.jpg',
                    ]
                ])
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Сейчас, находясь тут, рядом с Ремесленной школой, позвольте мне поделиться с вами моими воспоминаниями о том, как в Волышове обстояли дела с книгами.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, расскажите, Галина Васильевна']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, расскажите, галина васильевна':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Отлично, я сейчас начну свой рассказ. А пока вы его слушаете, предлагаю пройтись в сторону аллеи.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Ваша история увлекает, не останавливайтесь']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/Pro knigi.mp3",
            ]);
            break;

        case 'ваша история увлекает, не останавливайтесь':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Мы с вами находимся в начале аллеи, по которой я бы хотела с вами прогуляться и рассказать о жизни имения в первой половине XX века. Но позвольте мне сначала поделиться своими воспоминаниями о детстве в Волышове и рассказать, как сложилась моя жизнь после того, как я уехала учиться.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, продолжайте, пожалуйста']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, продолжайте, пожалуйста':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Отлично! Пожалуй, я начну свой рассказ, и мы продолжим нашу прогулку по аллее, которая выведет нас к въездному знаку «Псковский конный завод».",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Продолжайте рассказ']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/Pro leto v Volyshovo.mp3",
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-04-01.jpg',
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/Ob uchebe i rabote.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-05-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-05-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-05-03.jpg',
                    ]
                ])
            ]);
            break;

        case 'продолжайте рассказ':  
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Мне очень приятен ваш интерес к этим местам и моим воспоминаниям о них. И сейчас я бы хотела коснуться темы очень болезненной и вместе с тем очень важной не только в истории этих мест, но и всей нашей страны... Я бы хотела рассказать вам, что происходило, когда началась Вторая Мировая война и о послевоенных годах Волышово.",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Да, начинайте ваш расскажите!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'да, начинайте ваш расскажите!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/O velikoj otechestvennoj vojne.mp3",
            ]);
            sendTelegram('sendMediaGroup', [
                'chat_id' => $chat_id,
                'media' => json_encode([
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-06-01.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-06-02.jpg',
                    ],
                    [
                        'type' => 'photo',
                        'media' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-06-03.jpg',
                    ]
                ])
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/Poslevoennye gody.mp3",
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-07-01.jpg',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Не останавливайтесь на этом месте, продолжайте']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'не останавливайтесь на этом месте, продолжайте':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Ну что ж, вот мы с вами и дошли до конца аллеи, и вы сейчас видите перед собой въездной знак, что здесь находится Псковский конный завод. Он был основан в 1923 году на базе Графского конезавода. В 1941 году ценных породистых лошадей успели эвакуировать до того, как немцы заняли Псков и Порхов. «Эвакуация» растянулась на 1 год и две недели. Лошадей гнали своим ходом. Животные погибали, но, несмотря ни на что, после оккупации в Волышово вернулись 17 лошадей.\nВ родословных нынешних рысаков записаны предки в восьмом и даже девятом коленах. До 1990-х годов Псковский конезавод входил в пятерку лучших конезаводов СССР.\nА сейчас предлагаю прогуляться по аллее обратно в сторону усадьбы. А пока мы идем, позвольте я расскажу о том, кто жил в Волышове, и поделюсь своими размышлениями о феномене «русской усадьбы».",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Конечно, продолжайте ваш рассказ!']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;

        case 'конечно, продолжайте ваш рассказ!':
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/O naselenii Volyshovo.mp3",
            ]);
            sendTelegram('sendAudio', [
                'chat_id' => $chat_id,
                'audio' => "https://bot.vakhromeev.com/voiceofestate/audio/proskuryakova/epilog G.V.Proskuryakova.mp3",
            ]);
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<i>Развитие псковского краеведения в 1950-е гг. невозможно представить без Галины Васильевны Проскуряковой, стоявшей у его истоков и много сделавшей для организации краеведческой работы.</i>\nА. В. Филимонов\nСейчас Галина Васильевна порадовалась бы, что в Пскове существует своя научная школа историков и археологов, которые выполняют свой профессиональный долг.\nСкончалась Галина Васильевна 31 декабря 1992 года.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Завершить прогулку с Галиной Васильевной']
                        ]
                    ]
                ]
            ]);
            sendTelegram('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => 'https://bot.vakhromeev.com/voiceofestate/img/proskuryanova-08-01.jpg',
            ]);
            break;

        case 'завершить прогулку с галиной васильевной':
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Спасибо за нашу прогулку и до новых встреч здесь, в усадьбе Строгановых в Волышове. Если хотите, Вы можете продолжить прогулку или вернуться позже.",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Выбрать маршрут']
                        ],
                        [
                            ['text' => 'Спасибо, дальше я просто прогуляюсь по усадьбе'],
                        ]
                    ]
                ]
            ]);
            break;
        // Галина Васильевна Проскурякова. Конец

        case 'спасибо, дальше я просто прогуляюсь по усадьбе':
            $method = 'sendMessage';
            $send_data = [
                'chat_id' => $chat_id,
                'text' => "Конечно, продолжим в другой раз!\nПриятной прогулки!",
                'parse_mode' => 'html',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'управляющий охотой Григорий Павлович Пеший'],
                        ],
                        [
                            ['text' => 'графиня Татьяна Дмитриевна Строганова'],
                        ],
                        [
                            ['text' => 'управляющий конюшнями Карл Иванович Пейдж'],
                        ],
                        [
                            ['text' => 'граф Сергей Александрович Строганов'],
                        ],
                        [
                            ['text' => 'историк-краевед Галина Васильевна Проскурякова'],
                        ]
                    ]
                ]
            ];
            break;

        case (preg_match("/^[a-zA-Z0-9_\-.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9]+$/", $message) ? true : false):
            $method = 'sendMessage';
            file_get_contents(BASE_URL . $method . '?chat_id=' . $chatCollector . '&text=@' . $user['username'] . ' ' . $user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['id'] . ' ' . urlencode($message));
            $send_data = [
                'chat_id' => $chat_id,
                'text' => "Спасибо за почту. Будем на связи. " . $message
            ];
            break;

        case (preg_match("/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/", $message) ? true : false):
            $method = 'sendMessage';
            file_get_contents(BASE_URL . $method . '?chat_id=' . $chatCollector . '&text=@' . $user['username'] . ' ' . $user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['id'] . ' ' . urlencode($message));
            $send_data = [
                'chat_id' => $chat_id,
                'text' => "Спасибо за телефон. Будем на связи. " . $message
            ];
            break;
        
        default:
            $method = 'sendMessage';
            $send_data = [
                'chat_id' => $chat_id,
                'text' => 'Я пока не умею обрабатывать такие команды :('
            ];
    };
}

$res = sendTelegram($method, $send_data);

function sendTelegram($method, $data, $headers = []) {
    file_put_contents(__DIR__ . '/data.txt', print_r($data, 1)."\n", FILE_APPEND);
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => BASE_URL . $method,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $headers)
    ]);
    $result = curl_exec($curl);
    curl_close($curl);
    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
};
?>