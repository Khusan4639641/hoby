<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \App\Helpers\FileHelper;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Builder;

use Carbon\Carbon;

use App\Models\Collector;
use App\Models\CollectorTransaction;
use App\Traits\SmsTrait;
use App\Helpers\EncryptHelper;

use Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\FileUpload\InputFile;


class CoreController extends Controller
{
    use SmsTrait;

    private $update;

    private $message;

    private $chat_id;
    private $collector;

    // TODO: Refactoring SmsTrait: Remove $result, message & result from $this

    protected $result = [
        'status' => '',
        'response' => [
            'code' => '',
            'message' => [],
            'errors' => []
        ],
        'data' => [],
    ];

    /**
     * @param $type
     * @param $text
     */
    protected function message($type, $text)
    {
        $this->result['response']['message'][] = array(
            'type' => $type,
            'text' => $text
        );
    }

    /**
     * @return array|false|string
     */
    protected function result()
    {
        // если успешный запрос и код не задан, установить его в 200
        if ($this->result['status'] == 'success' && empty($this->result['response']['code'])) {
            $this->result['response']['code'] = 200;
        }

        $result = $this->result;

        return $result; //Request::input( 'api_token' ) ? json_encode( $result ) :$result;
    }

    public function webhook() {
        Telegram::commandsHandler(true);

        $this->initial();
    }

    public function initial() {
        $this->update = Telegram::getWebhookUpdates();

        if($this->update->callback_query) {
            $this->chat_id = $this->update->callback_query->from->id;

            $this->callback_query = $this->update->callback_query->data;
            $this->message = (object) [
                "type" => "callback_query",
                "value" => $this->update->callback_query->data,
                "message_id" => $this->update->callback_query->message->message_id
            ];
        } else if($this->update->message) {
            $this->chat_id = $this->update->message->from->id;

            if($this->update->message->location) {
                $this->message = (object) [
                    "type" => "location",
                    "value" => $this->update->message->location
                ];
            } else if($this->update->message->photo) {
                $this->message = (object) [
                    "type" => "photo",
                    "value" => $this->update->message->photo,
                    "media_group_id" => $this->update->message->media_group_id
                ];
            } else if($this->update->message->contact) {
                $this->message = (object) [
                    "type" => "contact",
                    "value" => $this->update->message->contact
                ];
            } else if($this->update->message->text) {
                $this->message = (object) [
                    "type" => "text",
                    "value" => $this->update->message->text
                ];
            } else {
                $this->message = (object) [
                    "type" => "undefined",
                    "value" => null
                ];
            }
        } else {
            $this->message = (object) [
                "type" => "undefined",
                "value" => null
            ];
        }

        $collector = Collector::where('chat_id', $this->chat_id)->first();

        if(!$collector) {
            $this->authProcess();
            return;
        } else {
            $this->collector = $collector;
            $this->collectorProcess();
        }
    }

    public function authProcess() {
        $phone = Redis::get("collector--{$this->chat_id}:phone");

        if($phone) {
            $this->phone_code_confirm($phone);
        } else {
            $this->get_phone();
        }
    }

    public function get_phone($skipCheck = false) {
        $message_text = 'Введите номер телефона:';
        $phone_number = $this->message->type === 'contact' ? $this->message->value->phone_number : $this->message->value;

        if(!$skipCheck && $phone_number && $phone_number !== '/start') {
            $valid_phone_number = valid_phone($phone_number);

            if($valid_phone_number) {
                if(!$this->find_collector($valid_phone_number)) {
                    Telegram::sendMessage([
                        'chat_id'      => $this->chat_id,
                        'text'         => 'Коллектор с данным номером отсутствует!'
                    ]);
                    $this->get_phone(true);
                    return;
                }
                $this->send_phone_code($valid_phone_number);
                return;
            } else {
                $message_text = 'Номер введен не верно. Повторите попытку:';
            }
        }

        $keyboard = [
            [
                [
                    'text' => 'Отправить мой номер',
                    'request_contact' => true,
                ],
            ],
        ];

        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        Telegram::sendMessage([
            'chat_id'      => $this->chat_id,
            'text'         => $message_text,
            'reply_markup' => $reply_markup
        ]);
    }

    public function send_phone_code($phone) {
        $phoneRequest = new Request();
        $phoneRequest->merge(['phone' => $phone]);
        $this->sendSmsCode($phoneRequest, true, 'Ваш код: :code');
        Redis::set("collector--{$this->chat_id}:phone", $phone);

        $this->get_phone_code();
    }

    public function get_phone_code() {
        $keyboard = [
            ['Изменить номер'],
            ['Отправить код заново'],
        ];

        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        Telegram::sendMessage([
            'chat_id'      => $this->chat_id,
            'text'         => 'Введите код:',
            'reply_markup' => $reply_markup
        ]);
    }

    public function phone_code_confirm($phone) {
        switch($this->message->value) {
            case 'Изменить номер':
                $this->change_phone($phone);
                break;
            case 'Отправить код заново':
                $this->send_phone_code($phone);
                break;
            default:
                $this->check_sms_code($phone);

        }
    }

    public function change_phone($phone) {
        $this->clear_redis($phone);
        $this->get_phone(true);
    }

    public function check_sms_code($phone) {
        $phoneRequest = new Request([
            'phone' => $phone,
            'code' => $this->message->value
        ]);
        $result = $this->checkSmsCode($phoneRequest);

        if($result['status'] === 'success') {
            $this->set_chat_id($phone);
        } else {
            Telegram::sendMessage([
                'chat_id'      => $this->chat_id,
                'text'         => 'Код введен не верно.'
            ]);
            $this->get_phone_code();
        }
    }


    public function set_chat_id($phone) {
        $this->find_collector($phone);

        $this->collector->chat_id = $this->chat_id;
        $this->collector->save();

        $this->clear_redis($phone);

        $this->collectorProcess(true);
    }

    public function clear_redis($phone) {
        Redis::del($phone);
        Redis::del("collector--{$this->chat_id}:phone");
    }

    public function find_collector($phone) {
        if($collector = Collector::whereHas('user', function (Builder $query) use($phone) {
            $query->where('phone', 'LIKE', $phone);
        })->first()) {
            $this->collector = $collector;
            return true;
        }

        return false;
    }

    public function collectorProcess($init = false) {
        $position = Redis::get("collector--{$this->chat_id}:position");

        $this->menuActions();

        if($position) {
            $this->checkPosition($position);
            return;
        }

        Redis::set("collector--{$this->chat_id}:position", 'menu');

        $keyboard = [
            ['🧾 Контракты'],
            // ['Настройки'],
        ];

        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        Telegram::sendMessage([
            'chat_id'      => $this->chat_id,
            'text' => 'Успешный вход!',
            'reply_markup' => $reply_markup
        ]);
    }

    public function checkReplies() {

    }

    public function checkPosition($position) {
        $content = explode('--', $position);

        switch($content[0]) {
            case 'contracts':
                $this->contractsActions();
                break;
            case 'contract':
                $this->contractActions($content[1]);
                break;
        }
    }

    public function menuActions() {
        switch($this->message->value) {
            case '🧾 Контракты':
                $this->get_contracts();
                break;
        }
    }

    public function contractsActions() {
        switch($this->message->type) {
            case('callback_query'):
                $this->contractsCallbacks();
                break;
        }
    }

    public function contractActions($value) {
        $content = explode(':', $value);

        if($this->message->type === 'callback_query') {
            $this->contract_callbacks();
            return;
        }

        switch($content[0]) {
            case('add'):
                $this->add_contract_content($content[1]);
                break;
            case('date'):
                $this->add_contract_date($content[1]);
                break;
        }
    }

    public function add_contract_content($contract_id) {
        $contract = $this->collector->contracts()->find($contract_id);

        $transaction = [
            'collector_contract_id' => $contract->pivot->id,
        ];

        $inline_markup = Keyboard::make()
            ->inline()
            ->row(
                Keyboard::inlineButton([
                    'text' => "Завершить",
                    'callback_data' => "contract--done:{$contract_id}"
                ]),
            );

        $message = [
            'chat_id' => $this->chat_id,
            'text' => "<b>Успешно добавлено. Вы можете добавить ещё:</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_markup
        ];

        switch($this->message->type) {
            case 'text':
                $transaction['type'] = 'text';
                $transaction['content'] = $this->message->value;
                break;
            case 'location':
                $transaction['type'] = 'location';
                $transaction['content'] = json_encode($this->message->value);
                break;
            case 'photo':
                $transaction['type'] = 'photo';
                $transaction['content'] = json_encode($this->message->value);
                $transaction['media_group_id'] = $this->message->media_group_id;
                break;
            default:
                $message['text'] = "<b>Не поддерживаемый формат. Повторите попытку!</b>";
                Telegram::sendMessage($message);
                return;
        }

        try {
            CollectorTransaction::create($transaction);
        } catch(Exception $e) {
            $message['text'] = "<b>Ошибка добавления. Повторите попытку!</b>";
        } finally {
            Telegram::sendMessage($message);
        }


    }

    public function add_contract_date($contract_id) {
        $contract = $this->collector->contracts()->find($contract_id);

        $transaction = [
            'collector_contract_id' => $contract->pivot->id,
        ];

        $message = [
            'chat_id' => $this->chat_id,
            'text' => "<b>Дата успешно добавлена</b>",
            'parse_mode' => 'HTML'
        ];



        switch($this->message->type) {
            case 'text':
                $transaction['type'] = 'date';
                $transaction['content'] = $this->message->value;
                break;
            default:
                $message['text'] = "<b>Не поддерживаемый формат. Повторите попытку!</b>";
                Telegram::sendMessage($message);
                return;
        }

        try {
            CollectorTransaction::create($transaction);
        } catch(Exception $e) {
            $message['text'] = "<b>Ошибка добавления. Повторите попытку!</b>";
        } finally {
            Telegram::sendMessage($message);
            $this->get_contract($contract_id);
        }


    }

    public function contractsCallbacks() {
        $content = explode('--', $this->message->value);

        switch($content[0]) {
            case 'contracts_list':
                $this->contracts_list($content[1]);
                break;
        }
    }

    public function contracts_list($value) {
        $content = explode(':', $value);

        switch($content[0]) {
            case 'contract':
                $this->get_contract($content[1]);
                break;
            case 'page':
                $this->get_contracts($content[1], $this->message->message_id);
                break;
        }
    }

    public function contract_callbacks() {
        $content = explode('--', $this->message->value);
        $value = explode(':', $content[1]);

        switch($value[0]) {
            case 'get':
                $this->get_contract_content($value[1]);
                break;
            case 'add':
                $this->set_contract_content($value[1]);
                break;
            case 'date':
                $this->set_contract_date($value[1]);
                break;
            case 'done':
                $this->get_contract($value[1]);
                break;
        }
    }

    public function get_contract_content($contract_id) {
        Redis::set("collector--{$this->chat_id}:position", "contract--get:{$contract_id}");

        $contract = $this->collector->contracts()->find($contract_id);

        $filePath = FileHelper::url($contract->act->path);
        $photo = InputFile::create($filePath, $contract->act->name);

        if(@file_get_contents($filePath) !== false) {
            Telegram::sendPhoto([
                'chat_id' => $this->chat_id,
                'photo' => $photo,
                'parse_mode' => 'HTML',
                'caption' => "<b>Контракт №</b> <code>{$contract->id}</code>"
            ]);
        } else {
            Telegram::sendMessage([
                'chat_id' => $this->chat_id,
                'text' => "<b>Контракт №</b> <code>{$contract->id}</code>: <b>Изображение не найдено</b>",
                'parse_mode' => 'HTML',
            ]);
        }
    }

    public function set_contract_content($contract_id) {
        Redis::set("collector--{$this->chat_id}:position", "contract--add:{$contract_id}");

        $contract = $this->collector->contracts()->find($contract_id);

        $text =  "<b>Контракт №</b> <code>{$contract->id}</code>: <b>Добавление материалов</b>";
        $text .= "\n\n";
        $text .= "Отправьте изображения, локацию или комментарии:";

        $inline_markup = Keyboard::make()
            ->inline()
            ->row(
                Keyboard::inlineButton([
                    'text' => "< Назад",
                    'callback_data' => "contract--done:{$contract_id}"
                ]),
            );

        Telegram::sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_markup
        ]);
    }

    public function set_contract_date($contract_id) {
        Redis::set("collector--{$this->chat_id}:position", "contract--date:{$contract_id}");

        $contract = $this->collector->contracts()->find($contract_id);

        $text =  "<b>Контракт №</b> <code>{$contract->id}</code>: <b>Добавление даты</b>";
        $text .= "\n\n";
        $text .= "Отправьте ожидаемую дату оплаты (Пример: 13.05.2022):";

        $inline_markup = Keyboard::make()
            ->inline()
            ->row(
                Keyboard::inlineButton([
                    'text' => "< Назад",
                    'callback_data' => "contract--done:{$contract_id}"
                ]),
            );

        Telegram::sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_markup
        ]);
    }

    public function get_contracts($page = 1, $message_id = null) {
        Redis::set("collector--{$this->chat_id}:position", 'contracts');

        $perPage = 5;
        $total = $this->collector->contracts()->count();
        $pageCount = ceil($total / $perPage);

        if($page < 1) {
            $page = 1;
        } else if($page > $pageCount) {
            $page = $pageCount;
        }

        $pageOffset = ($page - 1) * $perPage;
        $prevPage = $page > 1 ? $page - 1 : 1;
        $nextPage = $page < $pageCount ? $page + 1 : $pageCount;

        $contracts = $this->collector->contracts()
            ->with('buyer')
            ->limit($perPage)
            ->offset($pageOffset)
            ->get();

        $keyboard = Keyboard::make()
            ->inline();

        foreach($contracts as $contract) {
            $keyboard->row(Keyboard::inlineButton([
                'text' => "{$contract->id} - {$contract->buyer->phone}",
                'callback_data' => "contracts_list--contract:{$contract->id}"
            ]));
        }

        if($pageCount > 1) {
            $keyboard->row(
                Keyboard::inlineButton([
                    'text' => "<",
                    'callback_data' => "contracts_list--page:{$prevPage}"
                ]),
                Keyboard::inlineButton([
                    'text' => "{$page} / {$pageCount}",
                    'callback_data' => "contracts_list--page:{$page}"
                ]),
                Keyboard::inlineButton([
                    'text' => ">",
                    'callback_data' => "contracts_list--page:{$nextPage}"
                ]),
            );
        }



        $message = [
            'chat_id'      => $this->chat_id,
            'text'         => 'Список доступных контрактов:',
            'reply_markup' => $keyboard
        ];

        if($message_id) {
            $message['message_id'] = $message_id;
            Telegram::editMessageText($message);
        } else {
            Telegram::sendMessage($message);
        }
    }

    public function get_contract($contract_id) {
        Redis::set("collector--{$this->chat_id}:position", "contract--get:{$contract_id}");

        $contract = $this->collector->contracts()->find($contract_id);
        $buyer = $contract->buyer;

        $fio = $buyer->fio;
        $passport_number = EncryptHelper::decryptData($buyer->personals->passport_number);
        $registration_address = $buyer->addressRegistration->address;

        $delay_sum = $contract->append('delay_sum')->delay_sum;

        $text = "\n <b>Контракт №</b> <code>{$contract->id}</code>";

        $text .= "\n\n <b>Личные данные:</b>";
        $text .= "\n <b>- Ф.И.О:</b> <code>{$fio}</code>";
        $text .= "\n <b>- Паспорт:</b> <code>{$passport_number}</code>";
        $text .= "\n <b>- Прописка:</b> <code>{$registration_address}</code>";

        $text .= "\n\n <b>Доверители:</b>";
        foreach($buyer->guarants as $guarant) {
            $text .= "\n <b>- Ф.И.О:</b> <code>{$guarant->name}</code>";
            $text .= "\n   <b>Телефон:</b> <code>{$guarant->phone}</code>";
        }

        $text .= "\n\n <b>Контракт:</b>";
        $text .= "\n <b>- Задолжность:</b> <code>{$delay_sum} сум</code>";

        $inline_markup = Keyboard::make()
            ->inline()
            ->row(
                Keyboard::inlineButton([
                    'text' => "Фото акта",
                    'callback_data' => "contract--get:{$contract_id}"
                ]),
            )
            ->row(
                Keyboard::inlineButton([
                    'text' => "Добавить материал",
                    'callback_data' => "contract--add:{$contract_id}"
                ]),
            )
            ->row(
                Keyboard::inlineButton([
                    'text' => "Добавить ожидаемую дату оплаты",
                    'callback_data' => "contract--date:{$contract_id}"
                ]),
            );

        Telegram::sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_markup
        ]);
    }
}
