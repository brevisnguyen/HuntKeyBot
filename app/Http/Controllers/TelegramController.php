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
    protected $is_shift_start = false;
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
                    // $this->setUser($user);
                    // $this->setChat($chat);
                    
                    switch ($key) {
                        case 'start':
                            $this->start($user->id, $chat->id);
                            break;
                        case 'stop':
                            $this->stop($user->id, $chat->id);
                            break;
                        case 'grant':
                            $this->grant($user->id, $chat->id, $matches['user_name']);
                            break;
                        case 'revoke':
                            $this->revoke($user->id, $chat->id, $matches['user_name']);
                            break;
                        case 'deposit':
                            $this->deposit($user, $chat->id, $matches['deposit_amount']);
                            break;
                        case 'issued':
                            // $this->issued();
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
                    // Store Shift and Set Admin
                    $this->setShift($new_chat, $admin, false, true, false);
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
    public function setShift($chat, $user, $is_end = false, $is_admin = false, $is_operator = false)
    {
        $shift = Shift::create([
            'chat_id' => $chat->id,
            'usename' => $user->username,
            'is_end' => $is_end,
            'is_admin' => $is_admin,
            'is_operator' => $is_operator,
        ]);

        $key = 'telegram_shift_' . $chat->id;
        Cache::forever($key, $shift);
    }

    /**
     * Start Recording Transaction For This Day
     * @param int $user_id
     * @param int $chat_id
     */
    public function start($user_id, $chat_id)
    {
        if ( in_array('start', $this->getAllowedMethod($user_id)) ) {
            $key = 'telegram_start_recording_for_' . $chat_id;
            if ( (Cache::has($key) && Cache::get($key) == false) || Cache::has($key) == false) {
                // Sent Starting Message
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Bắt đầu ghi chép hoạt động.',
                ];
                $response = Telegram::bot()->sendMessage($params);

                Cache::put($key, true, now()->addHours(24));

            } elseif ( Cache::has($key) && Cache::get($key) == true ) {
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Hoạt động ghi đã được bật, không cần bật lại.',
                ];
                $response = Telegram::bot()->sendMessage($params);
            }
        } else {
            // Sent Reject Message
            $params = [
                'chat_id'   => $chat_id,
                'text'      => 'Bạn không có quyền hạn thực hiện hành động này.',
            ];
            $response = Telegram::bot()->sendMessage($params);
        }
    }

    /**
     * Stop Recording Transaction For This Day
     */
    public function stop($user_id, $chat_id)
    {
        if ( in_array('stop', $this->getAllowedMethod($user_id)) ) {
            $key = 'telegram_start_recording_for_' . $chat_id;
            if ( Cache::has($key) && Cache::get($key) == true ) {
                Cache::forever($key, false);

            } elseif ( (Cache::has($key) && Cache::get($key) == false) || Cache::has($key) != false ) {
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Hoạt động ghi cần được bật nếu muốn dừng.',
                ];
                $response = Telegram::bot()->sendMessage($params);
            }
        } else {
            // Sent Reject Message
            $params = [
                'chat_id'   => $chat_id,
                'text'      => 'Bạn không có quyền hạn thực hiện hành động này.',
            ];
            $response = Telegram::bot()->sendMessage($params);
        }
    }

    /**
     * Grant deposit/issued right to user
     */
    public function grant($admin_id, $chat_id, $username)
    {
        if ( in_array('grant', $this->getAllowedMethod($admin_id)) ) {

            $shift = Shift::create([
                'chat_id'       => $chat_id,
                'username'      => $username,
                'is_end'        => false,
                'is_admin'      => false,
                'is_operator'   => true,
            ]);
            $this->setAllowedMethod($username, 'operator');
            $params = [
                'chat_id'       => $chat_id,
                'text'          => 'Thêm quyền nhập/xuất cho tài khoản <a href="https://t.me/' . $username . '">@' . $username . ' . Thành công!',
                'parse_mode'    => 'HTML',
            ];
            $response = Telegram::bot()->sendMessage($params);

        } else {
            // Sent Reject Message
            $params = [
                'chat_id'   => $chat_id,
                'text'      => 'Bạn không có quyền hạn thực hiện hành động này.',
            ];
            $response = Telegram::bot()->sendMessage($params);
        }
    }

    /**
     * Revoke user right
     */
    public function revoke($admin_id, $chat_id, $username)
    {
        if ( in_array('grant', $this->getAllowedMethod($admin_id)) ) {

            $this->setAllowedMethod($username);
            $params = [
                'chat_id'       => $chat_id,
                'text'          => 'Xoá quyền nhập/xuất cho tài khoản <a href="https://t.me/' . $username . '">@' . $username . ' . Thành công!',
                'parse_mode'    => 'HTML',
            ];
            $response = Telegram::bot()->sendMessage($params);

        } else {
            // Sent Reject Message
            $params = [
                'chat_id'   => $chat_id,
                'text'      => 'Bạn không có quyền hạn thực hiện hành động này.',
            ];
            $response = Telegram::bot()->sendMessage($params);
        }
    }

    /**
     * Retrieving User Allowed Method
     */
    public function getAllowedMethod($username)
    {
        $key = 'telegram_user_method_' . $username;
        if ( Cache::has($key) ) {

            return Cache::get($key);

        } else {
            $this->setAllowedMethod($username);

            return $this->getAllowedMethod($username);
        }
    }

    /**
     * Store User Allowed Method
     */
    public function setAllowedMethod($username, $type = null)
    {
        $key = 'telegram_user_method_' . $username;

        if ( $type === 'admin' ) {

            Cache::forever($key, $this->admin_method);

        } elseif ( $type === 'operator' ) {

            Cache::forever($key, $this->operator_method);

        } else {
            Cache::forever($key, ['Nothing']);
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
            ]);
        } else {
            $user->is_bot       = $obj_user->is_bot;
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
            Cache::forever('telegram_chat_' . $chat->id, $obj_chat);
        } else {
            $old_chat = $this->getChat($obj_chat->id);
            if ( $old_chat != $obj_chat ) {
                $chat->type      =  $obj_chat->type;
                $chat->title     =  $obj_chat->title;
                $chat->username  =  $obj_chat->username;
                $chat->save();
                Cache::forever('telegram_chat_' . $chat->id, $obj_chat);
            }
        }

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
     * @param var $user
     * @param int $chat_id
     * @param float $amount
     * @return int id
     */
    public function deposit($user, $chat_id, $amount)
    {
        $key = 'telegram_start_recording_for_' . $chat_id;
        if ( Cache::get($key, false) == false ) {
            die();
        }
        if ( in_array('deposit', $this->getAllowedMethod($user->username)) ) {
            $deposit = Deposit::create([
                'user_id' => $user->id,
                'chat_id' => $chat_id,
                'amount' => $amount,
            ]);
            $key = 'newest_deposit_' . $chat_id;
            Cache::forever($key, $deposit->amount);

            return $deposit->id;
        } else {
            // Sent Reject Message
            $params = [
                'chat_id'   => $chat_id,
                'text'      => 'Bạn không có quyền hạn thực hiện hành động này.',
            ];
            $response = Telegram::bot()->sendMessage($params);
        }
    }

    /**
     * Store Issued
     * @param var $user
     * @param int $chat_id
     * @param float $amount
     * @return int id
     */
    public function issued($user, $chat_id, $amount)
    {
        $key = 'telegram_start_recording_for_' . $chat_id;
        if ( Cache::get($key, false) == false ) {
            die();
        }
        if ( in_array('issued', $this->getAllowedMethod($user->username)) ) {
            $issued = Issued::create([
                'user_id' => $user->id,
                'chat_id' => $chat_id,
                'amount' => $amount,
            ]);
            $key = 'newest_issued_' . $chat_id;
            Cache::forever($key, $issued->amount);

            return $issued->id;
        } else {
            // Sent Reject Message
            $params = [
                'chat_id'   => $chat_id,
                'text'      => 'Bạn không có quyền hạn thực hiện hành động này.',
            ];
            $response = Telegram::bot()->sendMessage($params);
        }
    }

    /**
     * Update balance
     */
    public function balance()
    {

    }

}
