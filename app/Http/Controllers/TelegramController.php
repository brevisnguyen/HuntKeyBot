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
use App\Models\Relationship;
use Telegram;

class TelegramController extends Controller
{
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
                            $this->start($user->username, $chat->id);
                            break;
                        case 'stop':
                            $this->stop($user->id, $chat->id);
                            break;
                        case 'grant':
                            $this->grant($user, $chat->id, $matches['user_name'], config('enums.operator.name'));
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
                    // Set Relationships
                    $this->setRelationships($new_chat->id, $admin->username, config('enums.admin.name'));
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
     * Start Recording Transaction For This Day
     * @param int $username
     * @param int $chat_id
     */
    public function start($username, $chat_id)
    {
        if ( in_array('start', $this->get_allowed_method($username, $chat_id)) ) {

            $shift_id = $this->create_shift($chat_id, true, false);
            if ($shift_id !== null) {
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Bắt đầu ghi chép giao dịch.',
                ];
                $response = Telegram::bot()->sendMessage($params);
            } else {
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Đã bắt đầu rồi. Không cần bật lại.',
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
        if ( in_array('stop', $this->get_allowed_method($user_id, $chat_id)) ) {

            $shift_id = $this->stop_shift($chat_id);
            if ($shift_id !== null) {
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Dừng phiên ghi chép.',
                ];
                $response = Telegram::bot()->sendMessage($params);
            } else {
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Phiên chưa bắt đầu hoặc có lỗi xảy ra.',
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
     * Create a Shift
     * @param int $chat_id
     */
    public function create_shift($chat_id, $is_start = false, $is_end = false)
    {
        $shift = Shift::whereChatId($chat_id)->whereIsStart(true)->whereIsEnd(false)->latest()->first();
        if ( $shift !== null ) {

            $shift = Shift::create([
                'chat_id'   => $chat_id,
                'is_start'  => $is_start,
                'is_end'    => $is_end,
            ]);

            return $shift->id;

        }
        return null;
    }

    /**
     * Stop a Shift
     */
    public function stop_shift($chat_id)
    {
        $shift = Shift::whereChatId($chat_id)->whereIsStart(true)->whereIsEnd(false)->latest()->first();
        if ( $shift !== null ) {
            $shift->is_start = true;
            $shift->is_end = true;
            $shift->save();

            return $shift->id;
        }
        return null;
    }

    /**
     * Grant operator right to user
     * @param Telegram\Bot\Objects\User $admin
     * @param int $chat_id
     * @param str $username
     * @param str $role
     */
    public function grant($admin, $chat_id, $username, $role)
    {
        if ( in_array('grant', $this->get_allowed_method($admin->username, $chat_id)) ) {
            $this->setRelationships($chat_id, $username, $role);

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
     * @param Telegram\Bot\Objects\User $admin
     * @param int $chat_id
     * @param int $username
     */
    public function revoke($admin, $chat_id, $username)
    {
        if ( in_array('revoke', $this->get_allowed_method($admin->username, $chat_id)) ) {
            
            $relationship = Relationship::whereUsername($username)->whereChatId($chat_id);
            if ( $relationship == null ) {
                $this->setRelationships($chat_id, $username, config('enums.guest.name'));
            
                $params = [
                    'chat_id'       => $chat_id,
                    'text'          => 'Xoá quyền nhập/xuất cho tài khoản <a href="https://t.me/' . $username . '">@' . $username . '</a>. Thành công!',
                    'parse_mode'    => 'HTML',
                ];
                $response = Telegram::bot()->sendMessage($params);

            } else {
                $params = [
                    'chat_id'       => $chat_id,
                    'text'          => '<a href="https://t.me/' . $username . '">@' . $username . '</a>. Chưa có quyền nào, không cần thu hồi.',
                    'parse_mode'    => 'HTML',
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
     * Set Relationships
     * @param int $chat_id
     * @param int $username
     * @param str $role
     */
    public function setRelationships($chat_id, $username, $role)
    {
        $relationship = Relationship::create([
            'chat_id' => $chat_id,
            'username' => $username,
            'role' => $role,
        ]);

        $this->setAllowedMethod($username, $chat_id, config('enums.' . $role . '.roles'));
    }

    /**
     * Store User Allowed Method
     * @param str $username
     * @param int $chat_id
     * @param Array $roles
     */
    public function setAllowedMethod($username, $chat_id, $roles)
    {
        $key = 'huntkey_bot_relationship_' . $username . '_in_' . $chat_id;

        Cache::forever($key, $roles);
    }

    /**
     * Retrieving User Allowed Method
     * @return Array
     */
    public function get_allowed_method($username, $chat_id)
    {
        $key = 'huntkey_bot_relationship_' . $username . '_in_' . $chat_id;

        if ( Cache::has($key) ) {
            return Cache::get($key);
        } else {
            $role = Relationship::whereChatId($chat_id)->whereUsername($username)->first();
            if ( $role !== null ) {
                return config('enums.' . $role . '.roles');
            }
        }
        return ['Nothing'];
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
     * @param Telegram\Bot\Objects\User $user
     * @param int $shift_id
     * @param float $amount
     * @return int id
     */
    public function deposit($user, $shift_id, $amount)
    {
        // $key = 'telegram_start_recording_for_' . $chat_id;
        // if ( Cache::get($key, false) == false ) {
        //     die();
        // }
        // if ( in_array('deposit', $this->get_allowed_method($user->username)) ) {
        //     $deposit = Deposit::create([
        //         'user_id' => $user->id,
        //         'chat_id' => $chat_id,
        //         'amount' => $amount,
        //     ]);
        //     $key = 'newest_deposit_' . $chat_id;
        //     Cache::forever($key, $deposit->amount);

        //     return $deposit->id;
        // } else {
        //     // Sent Reject Message
        //     $params = [
        //         'chat_id'   => $chat_id,
        //         'text'      => 'Bạn không có quyền hạn thực hiện hành động này.',
        //     ];
        //     $response = Telegram::bot()->sendMessage($params);
        // }
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
        if ( in_array('issued', $this->get_allowed_method($user->username, $chat_id)) ) {
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
