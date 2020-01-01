<?php

require_once 'PDOdb.php';;

//подтверждающий код
$confirmation_token = 'cond_token';
// токен
$token = "token";
//заполняем данные для подключения бд
$host = 'localhost';
$dbname = 'dbname';
$login = 'login';
$password = 'password';

$dbconn = new PDOdb($host, $dbname, $login, $password);

//Название теста
$nazvanie = "Узнай на сколько ты знаешь вселенную звездных войн";
//Правильные ответы
$protv = array(1, 3, 3, 1, 3);
//Варианты ответов. В каждом вопросе должно быть четыре варианта ответа
$varint = array(
    array("Боба Фет", "Джанго Фет", "Винду" ,"Дуку" ),
    array("Он узнал кто его отец", "Скайуокер поднял R2D2", "По анализу крови", "Его мама сказала"),
    array("Обивана Кеноби", "Джидайскую силу", "Песок", "Сепаратистов"),
    array("Акбар", "Пенфин", "Долсо", "Шин"),
    array("Хот", "Корусант", "Камино", "Джеонозис")
);
//Вопросы
$vopros = array ("Как звали сына наемника, отца которого убили на Джеонозис?",
    "Как Квайгон Джин подтвердил свою догадку, что Скайуокер обладает силой",
    "Во втором эпизоде Энакен говорил Падме что не любит X, что за этот X?",
    "Как звали одного из генералов который впервые появился в шестом эпизоде, проговоривший мемную фразу",
    "На какой планете было велось большое производство клонов во втором эпизоде?"
);

//Функция отвечающая за ответ vk
function writems($peer_id,$text, $keyboard)
{
    global $token;

    $request_params = array(
        'message' => $text,
        'keyboard' => json_encode($keyboard, JSON_UNESCAPED_UNICODE),
        'peer_id' => $peer_id,
        'access_token' => $token,
        'v' => '5.87');
    $get_params = http_build_query($request_params);
    file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
}
//help
function helpme()
{
    global $chat_id;

    writems($chat_id, "Напишете боту 'go' для того чтобы начать тест", 0);
    writems($chat_id, "Напишете боту 'rank' для того чтобы узнать ваше звание", 0);
    writems($chat_id, "Напишете боту 'attempt' для того чтобы узнать ваше количество попыток", 0);
    writems($chat_id, "Хорошей игры :)", 0);
}

//Выставление рангов на тест из 5 вопросов
function rang($pravilotv)
{
    global $chat_id;
    global $dbconn;

    if ($pravilotv == 0) {
        writems($chat_id, "Вы ничего не знаете :< ", 0);
        $dbconn->update('rank', 0, 'Новичок', false);
        writems($chat_id, "Теперь ваш ранг \"Новичок\"", 0);
    }
    if ($pravilotv == 1 || $pravilotv == 2) {
        writems($chat_id, "Вам бы стоило больше увлечься темой", 0);
        $dbconn->update('rank', 0, 'Новичок', false);
        writems($chat_id, "Теперь ваш ранг \"Новичок\"", 0);
    }
    if ($pravilotv == 3 || $pravilotv == 4) {
        writems($chat_id, "Неплохо, но можно лучше :)", 0);
        $dbconn->update('rank', 0, 'Любитель', false);
        writems($chat_id, "Теперь ваш ранг \"Любитель\"", 0);
    }
    if ($pravilotv == 5) {
        writems($chat_id, "Вы отлично знаете материал! :)", 0);
        $dbconn->update('rank', 0, 'Профи', false);
        writems($chat_id, "Теперь ваш ранг \"Профи\"", 0);
    }
}

//превращаем в массив json
$data = json_decode(file_get_contents('php://input'));
switch ($data->type) {
    case 'confirmation':    //если получили событие отвечающее за проверку
        echo $confirmation_token;
        break;

    case 'message_new':     //если получили события message

        $message_text = $data->object->text;
        $chat_id = $data->object->peer_id;
        $payload = $data->object->payload;
        $userId = $data->object->from_id;

        //берем информацию о пользователе
        $userInfo = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$userId}&v=5.87&access_token=".$token));

        //берем Имя со взятых данных
        $namename = $userInfo->response[0]->first_name;

        //проверяем существование пользователя в бд, если нет то заполняем его
        $result = $dbconn->select('nomer', $chat_id);
        if ($result == 0) {
            //SQL запрос
            $dbconn->insert($chat_id, 'Новичок', 0, 0, $namename, 0, 0);
            $nomer['nomer'] = 0;
            writems($chat_id, "Добро пожаловать на наш тест :) ".$namename, 0 );
            writems($chat_id, "Вам предстоит ответить на ".count($protv). " вопросов", 0 );
            writems($chat_id, "Напишите 'go' чтобы начать", 0);
            writems($chat_id, $nazvanie, 0 ); //пишем название теста
            echo 'ok';
            break;
        }

        //присваеваем значения была ли нажата та кнопка
        $bone = $payload == "{\"button\":\"1\"}";
        $btwo = $payload == "{\"button\":\"2\"}";
        $bthree = $payload == "{\"button\":\"3\"}";
        $bfour = $payload == "{\"button\":\"4\"}";

        //смотрим на каком номере вопроса находится пользователь
        $nomer = $dbconn->select('nomer', $chat_id);
        //проверяем нажатие кнопки
        if ($bone OR $btwo OR $bthree OR $bfour) {

            //если он ответил правильно то увеличиваем занчения правильности ответов на 1
            if ($payload == "{\"button\":\"".$protv[$nomer['nomer']]."\"}") {
                $dbconn->update("pravilotv","pravilotv + 1",$chat_id, true);
            }
            //переходим на следующий вопрос
            $nomer['nomer'] += 1;
            //также записываем это в бд
            $dbconn->update('nomer', $nomer['nomer'], $chat_id, false);

            //прошел ли тест пользователь
            if ($nomer['nomer'] == count($protv)) {
                $pravilotv = $dbconn->select('pravilotv', $chat_id);
                writems($chat_id, "Вы ответили на ".$pravilotv['pravilotv']." из ".count($protv)." вопросов ", 0);
                $dbconn->update('rank', 'Бывалый', $chat_id, false);
                //присваеваем значение активности клавиатуры нулю
                $dbconn->update('active', 0, $chat_id, false);
                //присвоение рангов и вывод комментария
                rang($pravilotv['pravilotv']);

                echo 'ok';
                break;
            }

        }

        //если пользователь начал тест
        if (mb_strtolower($message_text) == "go") {
            //увеличиваем количество попыток на 1
            $dbconn->update('popitki', 'popitki', $chat_id, true);
            //количество правильных ответов и номер вопроса на которой он отвечает делаем начальными
            $dbconn->update('nomer', 0, $chat_id, false);
            $dbconn->update('pravilotv', 0, $chat_id, false);
            //присваемваем активность клавиатуры к 1
            $dbconn->update('active', 1, $chat_id, false);
            $nomer['nomer'] = 0;
        }

        //выесняем какая активность у клавиатуры
        $active = $dbconn->select('active', $chat_id);
        //если была нажата кнопка или пользователь написал начать тест или активность клавиатуры = 1 то показываем клавиатуру
        if (mb_strtolower($message_text) == "go" || $bone || $btwo || $bthree || $bfour || $active['active'] == 1) {

            //конструкция клавиатуры
            $keyboard = [
                "one_time" => true,
                "buttons" => [
                    [
                        [
                            "action" =>
                                [
                                    "type" => "text",
                                    "payload" => "{\"button\": \"1\"}",
                                    "label" => $varint[$nomer['nomer']][0]
                                ],
                            "color" => "default"
                        ],
                        [
                            "action" =>
                                [
                                    "type" => "text",
                                    "payload" => "{\"button\": \"2\"}",
                                    "label" => $varint[$nomer['nomer']][1]
                                ],
                            "color" => "default"
                        ]

                    ],
                    [
                        [
                            "action" =>
                                [
                                    "type" => "text",
                                    "payload" => "{\"button\": \"3\"}",
                                    "label" => $varint[$nomer['nomer']][2]
                                ],
                            "color" => "default"
                        ],
                        [
                            "action" =>
                                [
                                    "type" => "text",
                                    "payload" => "{\"button\": \"4\"}",
                                    "label" => $varint[$nomer['nomer']][3]
                                ],
                            "color" => "default"
                        ]
                    ]
                ]
            ];
            writems($chat_id,  $vopros[$nomer['nomer']], $keyboard );
        }
        //вызов help
        if (mb_strtolower($message_text) == "help") {
            helpme();
        }
        //узнать звание
        if (mb_strtolower($message_text) == "rank") {
            $rank = $dbconn->select('rank', $chat_id);
            writems($chat_id, "У вас звание \"".$rank['rank']."\"", 0);
        }
        //узнать кол-во попыток
        if (mb_strtolower($message_text) == "attempt") {
            $attempt = $dbconn->select('popitki', $chat_id);
            writems($chat_id, "Вы уже совершили ". $attempt['popitki']." попытки", 0);
        }

        //посылаем ок
        echo 'ok';
        break;
}


?>