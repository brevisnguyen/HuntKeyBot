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
     * Create a WorkShift
     * @param int $chat_id
     */
    public function create_shift($chat_id, $is_start, $is_end)
    {
        $shift = WorkShift::whereChatId($chat_id)->whereIsStart($is_start)->whereIsEnd($is_end)->latest('start_time')->first();
        if ( $shift === null ) {

            $shift = WorkShift::create([
                'chat_id'   => $chat_id,
                'is_start'  => $is_start,
                'is_end'    => $is_end,
            ]);

            return $shift->id;

        }
        return null;
    }

    /**
     * Stop a WorkShift
     */
    public function stop_shift($chat_id)
    {
        $shift = WorkShift::whereChatId($chat_id)->whereIsStart(true)->whereIsEnd(false)->latest('stop_time')->first();
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
            if ( $admin->username == $username ) {
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Bạn không thể gán/xoá quyền của chính mình.',
                ];
                $response = Telegram::bot()->sendMessage($params);
                die();
            }

            $chat = Chat::find($chat_id);
            $state =  $chat->users()->sync([$username => ['role' => $role]]);
            if ( $state['updated'] || $state['attached']) {

                $this->setAllowedMethod($username, $chat_id, config('enums.' . $role . '.roles'));
                $params = [
                    'chat_id'       => $chat_id,
                    'text'          => 'Thêm quyền nhập/xuất cho tài khoản <a href="https://t.me/' . $username . '">@' . $username . '</a> . Thành công!',
                    'parse_mode'    => 'HTML',
                ];
                $response = Telegram::bot()->sendMessage($params);
            } else {
                $params = [
                    'chat_id'       => $chat_id,
                    'text'          => '<a href="https://t.me/' . $username . '">@' . $username . '</a>. Đã thêm, không cần thêm nữa.',
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
     * Revoke user right
     * @param Telegram\Bot\Objects\User $admin
     * @param int $chat_id
     * @param int $username
     */
    public function revoke($admin, $chat_id, $username)
    {
        if ( in_array('revoke', $this->get_allowed_method($admin->username, $chat_id)) ) {
            if ( $admin->username == $username ) {
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Bạn không thể gán/xoá quyền của chính mình.',
                ];
                $response = Telegram::bot()->sendMessage($params);
                die();
            }

            $chat = Chat::find($chat_id);
            $state =  $chat->users()->sync([$username => ['role' => config('enums.guest.name')]]);
            if ( $state['updated']  || $state['attached'] ) {

                $this->setAllowedMethod($username, $chat_id, config('enums.guest.roles'));
            
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
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Phiên chưa bắt đầu.',
                ];
                $response = Telegram::bot()->sendMessage($params);
                die();
            }

            $deposit = User::find($user->id)->deposits()->create([
                'shift_id'   => $shift_id,
                'amount'     => $amount,
                'created_at' => date('Y-m-d H:i:s', time()),
            ]);

            $this->balance($shift_id, $chat_id);

            $key = 'newest_deposit_in_' . $chat_id;
            Cache::forever($key, $deposit->amount);

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
                $params = [
                    'chat_id'   => $chat_id,
                    'text'      => 'Phiên chưa bắt đầu.',
                ];
                $response = Telegram::bot()->sendMessage($params);
                die();
            }

            $issued = User::find($user->id)->issueds()->create([
                'shift_id'   => $shift_id,
                'amount'     => $amount,
                'created_at' => date('Y-m-d H:i:s', time()),
            ]);

            $this->balance($shift_id, $chat_id);

            $key = 'newest_issued_in_' . $chat_id;
            Cache::forever($key, $issued->amount);

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
    public function balance($shift_id, $chat_id)
    {
        $deposits = WorkShift::find($shift_id)->deposits()->latest()->get();
        $issueds = WorkShift::find($shift_id)->issueds()->latest()->get();

        $total_deposit = count($deposits);
        $total_issued = count($issueds);

        $amount_deposit = 0;
        $text_deposit = '<b>Deposit ('. $total_deposit .') :</b>' . '
';
        foreach ($deposits as $key => $deposit) {
            $amount_deposit += $deposit->amount;
            if ( $key <= 4 ) {
                $text_deposit .= '<code>' . $deposit->created_at . '</code> : <b>' . $deposit->amount . '</b>' . '
';
            }
        }

        $amount_issued = 0;
        $text_issued = '<b>Issued ('. $total_issued .') :</b>' . '
';
        foreach ($issueds as $key => $issued) {
            $amount_issued += $issued->amount;
            if ( $key <= 4 ) {
                $text_issued .= '<code>' . $issued->created_at . '</code> : <b>' . $issued->amount . '</b>' . '
';
            }
        }

        $not_issued = $amount_deposit - $amount_issued;
        $text_statistic = '<b>Issued Available: ' . $amount_deposit . '</b>' . '
' . '<b>Total Issued: ' . $amount_issued .'</b>' . '
' . '<b>Not Issued: ' . $not_issued . '</b>';

        $params = [
            'chat_id'   => $chat_id,
            'text'      => $text_deposit . '
' . $text_issued . '
' . $text_statistic,
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
