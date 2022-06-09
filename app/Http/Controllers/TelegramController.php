<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Chat;
use App\Models\Issued;
use App\Models\Deposit;
use App\Models\WorkShift;
use App\Models\UserChat;
use Telegram;

date_default_timezone_set('Asia/Manila');

class TelegramController extends Controller
{
    protected $triggers = [
        'start'     => '/^(开始)$/',
        'stop'      => '/^(结束)$/',
        'clear'     => '/^(clear)^/',
        'grant'     => '/(?<=^设置操作人)\s+?@(?P<user_name>\w+)$/',
        'revoke'    => '/(?<=^删除操作人)\s+?@(?P<user_name>\w+)$/',
        'deposit'   => '/^入款\s?(?P<deposit_amount>\d+?)$/',
        'issued'    => '/^下发\s?(?P<issued_amount>\d+?)$/',
    ];

    /**
     * Retrieving Telegram WebHooks and Process Activity
     * 
     * getWebhookUpdate()
     */
    public function process()
    {
        $update = Telegram::bot()->getWebhookUpdate();
        // $update = Telegram::bot()->getUpdates();
        if ( empty($update) ) {
            return json_encode(['Nothing to updates']);
        }
        // $update = end($update);
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

                    switch ($key) {
                        case 'start':
                            $this->start($user->username, $chat->id);
                            break;
                        case 'stop':
                            $this->stop($user->username, $chat->id);
                            break;
                        case 'grant':
                            $this->grant($user, $chat->id, $matches['user_name'], config('enums.operator.name'));
                            break;
                        case 'revoke':
                            $this->revoke($user, $chat->id, $matches['user_name']);
                            break;
                        case 'deposit':
                            $this->deposit($user, $chat->id, $matches['deposit_amount']);
                            break;
                        case 'issued':
                            $this->issued($user, $chat->id, $matches['issued_amount']);
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
                    $this->set_user_chat($new_chat->id, $admin->username, config('enums.admin.name'));
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
     * @param str $username
     * @param int $chat_id
     */
    public function start($username, $chat_id)
    {
        if ( in_array('start', $this->get_allowed_method($username, $chat_id)) ) {

            $shift = Chat::find($chat_id)
                ->work_shifts()
                ->whereIsStart(true)
                ->whereIsEnd(false)
                ->first();

            if ( $shift ) {
                $response = Telegram::bot()->sendMessage([
                    'chat_id'   => $chat_id,
                    'text'      => '机器人已开始记录今天账单。',
                ]);

            } else {
                $new_shift = Chat::find($chat_id)->work_shifts()->create([
                    'is_start'   => true,
                    'is_stop'    => false,
                    'start_time' => date("Y-m-d H:i:s", time())
                ]);

                $response = Telegram::bot()->sendMessage([
                    'chat_id'   => $chat_id,
                    'text'      => '开始记录今天账单。',
                ]);
            }

        } else {
            // Sent Reject Message
            $response = Telegram::bot()->sendMessage([
                'chat_id'   => $chat_id,
                'text'      => '你没有权限啦。',
            ]);
        }
    }

    /**
     * Stop Recording Transaction For This Day
     */
    public function stop($user_id, $chat_id)
    {
        if ( in_array('stop', $this->get_allowed_method($user_id, $chat_id)) ) {

            $shift = Chat::find($chat_id)
                ->work_shifts()
                ->whereIsStart(true)
                ->whereIsEnd(false)
                ->first();
            
            if ( $shift ) {

                $shift->is_end = true;
                $shift->stop_time = date("Y-m-d H:i:s", time());
                $shift->save();

                $response = Telegram::bot()->sendMessage([
                    'chat_id'   => $chat_id,
                    'text'      => '结束记录。'
                ]);

            } else {
                $response = Telegram::bot()->sendMessage([
                    'chat_id'   => $chat_id,
                    'text'      => '记录今天账单还没开始。',
                ]);
            }

        } else {
            // Sent Reject Message
            $response = Telegram::bot()->sendMessage([
                'chat_id'   => $chat_id,
                'text'      => '你没有权限啦。',
            ]);
        }
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
            if ( $admin->username == $username ) {
                $response = Telegram::bot()->sendMessage([
                    'chat_id'   => $chat_id,
                    'text'      => '不能设置或删除自己的权限。',
                ]);
                die();
            }

            try {
                $userchat = UserChat::updateOrCreate(
                    ['username' => $username, 'chat_id' => $chat_id],
                    ['role' => $role],
                );

                $this->setAllowedMethod($username, $chat_id, config('enums.' . $role . '.roles'));
                $params = [
                    'chat_id'       => $chat_id,
                    'text'          => '设置<a href="https://t.me/' . $username . '">@' . $username . '</a>作为操作人完成。',
                    'disable_web_page_preview' => true,
                    'parse_mode'    => 'HTML',
                ];
                $response = Telegram::bot()->sendMessage($params);

            } catch ( \Throwable $th ) {
                $response = Telegram::bot()->sendMessage([
                    'chat_id'       => $chat_id,
                    'text'          => '发生错误，请稍后再试。',
                ]);
            }

        } else {
            // Sent Reject Message
            $response = Telegram::bot()->sendMessage([
                'chat_id'   => $chat_id,
                'text'      => '你没有权限啦。',
            ]);
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
            if ( $admin->username == $username ) {
                $response = Telegram::bot()->sendMessage([
                    'chat_id'   => $chat_id,
                    'text'      => '不能设置或删除自己的权限。',
                ]);
                die();
            }

            try {
                $userchat = UserChat::updateOrCreate(
                    ['username' => $username, 'chat_id' => $chat_id],
                    ['role' => config('enums.guest.name')],
                );
                $this->setAllowedMethod($username, $chat_id, config('enums.guest.roles'));

                $params = [
                    'chat_id'       => $chat_id,
                    'text'          => '删除操作人<a href="https://t.me/' . $username . '">@' . $username . '</a>完成。',
                    'disable_web_page_preview' => true,
                    'parse_mode'    => 'HTML',
                ];
                $response = Telegram::bot()->sendMessage($params);

            } catch ( \Throwable $th ) {
                $response = Telegram::bot()->sendMessage([
                    'chat_id'       => $chat_id,
                    'text'          => '发生错误，请稍后再试。',
                ]);
            }

        } else {
            // Sent Reject Message
            $response = Telegram::bot()->sendMessage([
                'chat_id'   => $chat_id,
                'text'      => '你没有权限啦。',
            ]);
        }
    }

    /**
     * Set Relationships
     * @param int $chat_id
     * @param int $username
     * @param str $role
     */
    public function set_user_chat($chat_id, $username, $role)
    {
        $chat = Chat::find($chat_id);
        $chat->users()->attach($username, ['role' => $role]);

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

        return Cache::has($key) ? Cache::get($key) : ['Nothing'];
    }

    /**
     * Store user
     * @param User $obj_user
     */
    public function setUser($obj_user)
    {
        $user = User::updateOrCreate(
            [ 'id' => $obj_user->id ],
            [ 'username' => $obj_user->username, 'first_name' => $obj_user->first_name, 'last_name' => $obj_user->last_name ]
        );
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
        $chat = Chat::updateOrCreate(
            [ 'id' =>  $obj_chat->id ],
            [ 'type' => $obj_chat->type, 'title' => $obj_chat->title, 'username' => $obj_chat->username ]
        );
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
     * @param int $chat_id
     * @param float $amount
     * @return int id
     */
    public function deposit($user, $chat_id, $amount)
    {
        if ( in_array('deposit', $this->get_allowed_method($user->username, $chat_id)) ) {

            $shift_id = $this->get_current_shift_id($chat_id);

            if ( $shift_id === null ) {
                $response = Telegram::bot()->sendMessage([
                    'chat_id'   => $chat_id,
                    'text'      => '记录今天账单还没开始。',
                ]);
                die();
            }

            $deposit = User::find($user->id)->deposits()->create([
                'shift_id'   => $shift_id,
                'amount'     => $amount,
                'created_at' => date('Y-m-d H:i:s', time()),
            ]);

            // $deposit_key = 'huntkey_bot_total_deposit_in_' . $chat_id . '_in_shift_id_' . $shift_id;
            // $value = Cache::get($deposit_key) + 1;
            // Cache::forever($deposit_key, $value);

            $this->balance($shift_id, $chat_id);

            $key = 'newest_deposit_in_' . $chat_id;
            Cache::forever($key, $deposit->amount);

        } else {
            // Sent Reject Message
            $response = Telegram::bot()->sendMessage([
                'chat_id'   => $chat_id,
                'text'      => '你没有权限啦。',
            ]);
        }
    }

    /**
     * Store Issued
     * @param Telegram\Bot\Objects\User $user
     * @param int $chat_id
     * @param float $amount
     * @return int id
     */
    public function issued($user, $chat_id, $amount)
    {
        if ( in_array('issued', $this->get_allowed_method($user->username, $chat_id)) ) {

            $shift_id = $this->get_current_shift_id($chat_id);

            if ( $shift_id === null ) {
                $response = Telegram::bot()->sendMessage([
                    'chat_id'   => $chat_id,
                    'text'      => '记录今天账单还没开始。',
                ]);
                die();
            }

            $issued = User::find($user->id)->issueds()->create([
                'shift_id'   => $shift_id,
                'amount'     => $amount,
                'created_at' => date('Y-m-d H:i:s', time()),
            ]);

            // $issued_key = 'huntkey_bot_total_issued_in_' . $chat_id . '_in_shift_id_' . $shift_id;
            // $value = Cache::get($issued_key) + 1;
            // Cache::forever($issued_key, $value);

            $this->balance($shift_id, $chat_id);

            $key = 'newest_issued_in_' . $chat_id;
            Cache::forever($key, $issued->amount);

        } else {
            // Sent Reject Message
            $response = Telegram::bot()->sendMessage([
                'chat_id'   => $chat_id,
                'text'      => '你没有权限啦。',
            ]);
        }
    }

    /**
     * Update balance
     */
    public function balance($shift_id, $chat_id)
    {
        $deposits = WorkShift::find($shift_id)->deposits()->latest()->get();
        $issueds = WorkShift::find($shift_id)->issueds()->latest()->get();

        $total_deposit = count($deposits);
        $total_issued = count($issueds);

        $amount_deposit = 0;
        $text_deposit = '<b>入款（'. $total_deposit .'笔）：</b>' . '
';
        foreach ($deposits as $key => $deposit) {
            $amount_deposit += $deposit->amount;
            if ( $key < 4 ) {
                $text_deposit .= '<code>' . $deposit->created_at . '</code> : <b>' . $deposit->amount . '</b>' . '
';
            }
        }

        $amount_issued = 0;
        $text_issued = '<b>下发（'. $total_issued .'笔）：</b>' . '
';
        foreach ($issueds as $key => $issued) {
            $amount_issued += $issued->amount;
            if ( $key < 4 ) {
                $text_issued .= '<code>' . $issued->created_at . '</code> : <b>' . $issued->amount . '</b>' . '
';
            }
        }

        $url = '<a href="'. route('telegram.history', ['chat_id' => $chat_id]) . '">点击跳转完整账单</a>';
        $not_issued = $amount_deposit - $amount_issued;
        $text_statistic = '<b>应下发：' . $amount_deposit . '</b>' . '
' . '<b>总下发：' . $amount_issued .'</b>' . '
' . '<b>未下发：' . $not_issued . '</b>';

        $params = [
            'chat_id'   => $chat_id,
            'text'      => $text_deposit . '
' . $text_issued . '
' . $text_statistic . '
' . $url,
            'parse_mode'    => 'HTML',
        ];
        $response = Telegram::bot()->sendMessage($params);

    }

    /**
     * Retrieving Current Shift Id
     */
    public function get_current_shift_id($chat_id)
    {
        $shift = WorkShift::whereChatId($chat_id)->whereIsStart(true)->whereIsEnd(false)->first();
        return $shift !== null ? $shift->id : null;
    }

}
