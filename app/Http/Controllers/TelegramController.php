<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
// use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\User;
use App\Models\Chat;
use App\Models\Issued;
use App\Models\Deposit;
use App\Models\Shift;
use Telegram;

class TelegramController extends Controller
{
    protected $is_start = false;
    protected $current_chat_id = 0;

    protected $admin_method = ['start', 'stop', 'clear', 'grant', 'revoke', 'deposit', 'issued'];
    protected $operator_method = ['deposit', 'issued'];

    protected $triggers = [
        'start'     => '/^(start)$/',
        'stop'      => '/^(stop)$/',
        'clear'     => '/^(clear)^/',
        'grant'     => '/(?<=^add operator)\s+?@(?P<user_name>\w+)$/',
        'revoke'    => '/(?<=^remove operator)\s+?@(?P<user_name>\w+)$/',
        'deposit'   => '/^deposit\s+?(?P<deposit_amount>\d+?)$/',
        'issued'    => '/^issued\s+?(?P<issued_amount>\d+?)$/',
    ];

    /**
     * Retrieving Telegram WebHooks and Process Activity
     * 
     * getWebhookUpdate()
     */
    public function process()
    {
        // $update = Telegram::bot()->getWebhookUpdate();
        $update = Telegram::bot()->getUpdates();
        if ( empty($update) ) {
            return json_encode(['Nothing to updates']);
        }
        $update = end($update);
        $message = $update->getMessage();

        // dd($update);
        // Only allowed text message
        if ( $message->objectType() === 'text' ) {

            $user = $message->from;
            $chat = $message->chat;
            $text = $message->text;
            $entities = $message->entities ?? null;
            
            foreach ( $this->triggers as $key => $trigger ) {
                // $this->run($trigger, $text, $entities);
                $isMatch = preg_match($trigger, $text, $matches);
                if ( $isMatch == 1) {
                    $this->setUser($user);
                    $this->setChat($chat);
                    
                    switch ($key) {
                        case 'start':
                            # code...
                            break;
                            
                            default:
                            # code...
                            break;
                        }
                        
                } elseif ( $isMatch === false ) {
                // Send Error To Bot's Boss
                }
            }
        } elseif ( $message->objectType() === 'new_chat_members' ) {
            $admin = $message->from;
            $new_chat = $message->chat;
            $new_members = $message->new_chat_members;
            foreach ( $new_members as $new_member ) {
                // Some one add Bot to Group
                if ( $new_member->is_bot && $new_member->id == env('TELEGRAM_BOT_ID') ) {
                    // Send welcome
                    $response = Telegram::bot()->sendMessage([
                        'chat_id' => $new_chat->id,
                        'text' => 'Cảm ơn <a href="tg://user?id=' . $admin->id . '">' . $admin->last_name . ' ' . $admin->first_name .'</a> đã thêm tôi vào nhóm.',
                        'parse_mode' => 'HTML',
                    ]);
                    // Store Chats
                    $this->setChat($new_chat);
                    // Store Admin
                    $this->setUser($admin);
                    // Store Shift
                    $this->setShift($new_chat, $admin);
                }
            }
        } elseif ( $message->objectType() === 'left_chat_member' ) {
            $admin = $message->from;
            $left_chat = $message->chat;
            $left_member = $message->left_chat_member;

            if ( $left_member->is_bot && $left_member->id == env('TELEGRAM_BOT_ID') ) {
                $response = Telegram::bot()->sendMessage([
                    'chat_id' => $admin->id,
                    'text' => 'Xin lỗi vì đã làm bạn thất vọng. Nếu muốn tôi quay lại hay thêm <a href="https://t.me/' . $left_member->username . '">@' . $left_member->username . '</a> vào nhóm nhé.',
                    'parse_mode' => 'HTML',
                ]);
            }
        }
    }

    /**
     * Store Shift When The Bot Be Added To Group
     * 
     * Set User To Admin Who Added Bot
     */
    public function setShift($chat, $admin)
    {
        $shift = Shift::create([
            'chat_id' => $chat->id,
            'user_id' => $admin->id,
            'is_end' => false,
            'is_admin' => true,
            'is_operator' => false,
        ]);

        $key_1 = 'telegram_shift_' . $chat->id . '_admin_id';
        $key_2 = 'telegram_shift_' . $chat->id . '_isEnd';
        Cache::forever($key_1, $admin->id);
        Cache::forever($key_2, $shift->is_end);
    }

    /**
     * Start Recording Transaction For This Day
     */
    public function start($user_id)
    {
        if ( in_array('start', $this->getAllowedMethod($user_id)) ) {
            $key = 'telegram_start_recording';
            if ( Cache::has($key) ) {
                // Started
                $params = [
                    'chat_id'   => '',
                    'text'      => '',
                ];

            } else {
                Cache::put($key, true, now()->addHours(24));
            }
        }
    }

    /**
     * Retrieving User Allowed Method
     */
    public function getAllowedMethod($user_id)
    {
        $key = 'telegram_user_method_' . $user_id;
        if ( Cache::has($key) ) {

            return Cache::get($key);

        } else {
            $this->setAllowedMethod($user_id);

            return $this->getAllowedMethod($user_id);
        }
    }

    /**
     * Store User Allowed Method
     */
    public function setAllowedMethod($user_id)
    {
        $key = 'telegram_user_method_' . $user_id;

        $user = $this->getUser($user_id);
        if ( $user->is_admin ) {

            Cache::forever($key, $this->admin_method);

        } elseif ( $user->is_operator ) {

            Cache::forever($key, $this->operator_method);

        } else {

            Cache::forever($key, []);
        }
    }

    /**
     * Store user
     * @param User $obj_user
     */
    public function setUser($obj_user)
    {
        $user = User::find($obj_user->id);

        if ( $user == null ) {  // create new user
            $user = User::create([
                'id'            => $obj_user->id,
                'is_bot'        => $obj_user->is_bot,
                'username'      => $obj_user->username,
                'first_name'    => $obj_user->first_name,
                'last_name'     => $obj_user->last_name,
                'is_admin'      => false,
                'is_operator'   => false
            ]);
        } else {
            $user->username     = $obj_user->username;
            $user->first_name   = $obj_user->first_name;
            $user->last_name    = $obj_user->last_name;
            $user->save();
        }

        Cache::forever('telegram_user_' . $obj_user->id, $user);
    }

    /**
     * Retrieving User
     * @param int id
     * @return User
     */
    public function getUser($id)
    {
        $key = 'telegram_user_' . $id;
        
        if ( Cache::has($key) ) {

            return Cache::get($key);

        } else {
            $user = User::find($id);
            if ( $user !== null ) {
                Cache::forever('telegram_user_' . $user->id, $user);

                return $user;
            }
        }
    }

    /**
     * Set Chat
     * @param Chat $obj_chat
     */
    public function setChat($obj_chat)
    {
        $chat = Chat::find($obj_chat->id);

        if ( $chat == null ) {  // add new chat
            $chat = Chat::create([
                'id'         => $obj_chat->id,
                'type'       => $obj_chat->type,
                'title'      => $obj_chat->title ?? null,
                'username'   => $obj_chat->username ?? null,
            ]);
        } else {
            $chat->type      =  $obj_chat->type;
            $chat->title     =  $obj_chat->title ?? null;
            $chat->username  =  $obj_chat->username ?? null;
            $chat->save();
        }

        Cache::forever('telegram_chat_' . $chat->id, $chat);
    }

    /**
     * Retrieving Chat
     * @param int id
     * @return Chat
     */
    public function getChat($id)
    {
        $key = 'telegram_chat_' . $id;
        
        if ( Cache::has($key) ) {
            return Cache::get($key);
        } else {
            $chat = Chat::find($id);
            Cache::forever($key, $chat);
            return $chat;
        }
    }

    /**
     * Store Deposit
     * @param int $user_id
     * @param int $chat_id
     * @param float $amount
     * @return int id
     */
    public function setDeposit($user_id, $chat_id, $amount)
    {
        $deposit = Deposit::create([
            'user_id' => $user_id,
            'chat_id' => $chat_id,
            'amount' => $amount,
        ]);
        Cache::forever('newest_deposit', $deposit);

        return $deposit->id;
    }

    /**
     * Store Issued
     * @param int $user_id
     * @param int $chat_id
     * @param float $amount
     * @return int id
     */
    public function setIssued($user_id, $chat_id, $amount)
    {
        $issued = Issued::create([
            'user_id' => $user_id,
            'chat_id' => $chat_id,
            'amount' => $amount,
        ]);
        Cache::forever('newest_issued', $issued);

        return $issued->id;
    }

}
