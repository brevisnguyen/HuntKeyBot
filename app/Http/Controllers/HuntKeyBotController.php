<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram;
use App\Models\Chat;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Issued;
use App\Models\Shift;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Objects\Keyboard\InlineKeyboardButton;
use Telegram\Bot\Objects\Keyboard\InlineKeyboardMarkup;
use Telegram\Bot\Objects\User as TeleUser;

date_default_timezone_set('Asia/Manila');

class HuntKeyBotController extends Controller
{
    protected $activeBot;
    protected $activeMsgId;
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $bot = Telegram::bot($request->get('bot'));
        $this->activeBot = $bot;
        $update = $bot->getWebhookUpdate();

        if ( hash_equals('message', $update->objectType()) ) {
            $message = $update->getMessage();
            $this->activeMsgId = $message->get('message_id');
            $this->senderHandle(new TeleUser($message->from), $message->chat->id);

            if ( $message->has('entities') ) {
                if ( preg_match(config('enums.triggers.grant'), $message->text, $matches) == 1 ) {
                    if ( ! $this->isAdmin($message->chat->id, $message->from->id) ) {
                        $bot->sendMessage([
                            'chat_id'   => $message->chat->id,
                            'text'      => '您没有权限啦。',
                            'reply_to_message_id' => $message->message_id,
                        ]);
                        return;
                    }
                    foreach ( $message->get('entities') as $entity ) {
                        if ( hash_equals('text_mention', $entity->type) ) {

                            $this->grantRoles($message->chat->id, new TeleUser($entity->user));

                        } elseif ( hash_equals('mention', $entity->type) ) {

                            $username = mb_substr( $message->text, $entity->offset, $entity->length, 'UTF-8' );
                            $username = ltrim($username, '@');
                            $this->grantRoles($message->chat->id, $username, true);

                        }
                    }
                    $bot->sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => '设置操作人成功!',
                        'reply_to_message_id' => $message->message_id
                    ]);

                } elseif ( preg_match(config('enums.triggers.revoke'), $message->text, $matches) == 1 ) {
                    if ( ! $this->isAdmin($message->chat->id, $message->from->id) ) {
                        $bot->sendMessage([
                            'chat_id'   => $message->chat->id,
                            'text'      => '您没有权限啦。',
                        ]);
                        return;
                    }
                    foreach ( $message->get('entities') as $entity ) {
                        if ( hash_equals('text_mention', $entity->type) ) {

                            $this->revokeRoles($message->chat->id, $entity->user->id);

                        } else {

                            $username = mb_substr( $message->text, $entity->offset, $entity->length, 'UTF-8' );
                            $username = ltrim($username, '@');
                            $this->revokeRoles($message->chat->id, $username, true);

                        }
                    }
                    $bot->sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => '删除操作人成功!',
                        'reply_to_message_id' => $message->message_id
                    ]);
                }
            } else {
                foreach ( config('enums.triggers') as $key => $trigger ) {
                    $isMatch = preg_match($trigger, $message->text, $matches);
                    if ( $isMatch == 1 ) {
                        switch ($key) {
                            case 'start':
                                $this->startRecords($message->chat->id, $message->from->id);
                                break;
                            case 'stop':
                                $this->stopRecords($message->chat->id, $message->from->id);
                                break;
                            case 'deposit':
                                $this->depositHandle($message->chat->id, $message->from->id, $matches['amount']);
                                break;
                            case 'deposit_short':
                                $this->depositHandle($message->chat->id, $message->from->id, $matches['amount']);
                                break;
                            case 'issued':
                                $this->issuedHandle($message->chat->id, $message->from->id, $matches['amount']);
                                break;
                            case 'issued_short':
                                $this->issuedHandle($message->chat->id, $message->from->id, $matches['amount']);
                                break;
                            case 'rate':
                                $this->rateHandle($message->chat->id, $message->from->id, $matches['rate']);
                                break;
                            default:
                                # code...
                                break;
                        }
                    }
                }
            }

        } elseif ( hash_equals('my_chat_member', $update->objectType()) ) {
            $bot_update = $update->my_chat_member;
            $old_status = $bot_update->old_chat_member->status;
            $new_status = $bot_update->new_chat_member->status;

            if ( $old_status == 'left' && $new_status == 'member' ) {
                $chat = Chat::firstOrCreate(
                    ['id' => $bot_update->chat->id],
                    [
                        'type' => $bot_update->chat->type,
                        'title' => $bot_update->chat->title,
                        'username' => $bot_update->chat->username,
                    ],
                );

                $admins = $bot->getChatAdministrators(['chat_id' => $chat->id]);
                foreach ($admins as $admin) {
                    $record = User::updateOrCreate(
                        ['id' => $admin->user->id],
                        [
                            'username' => $admin->user->username,
                            'first_name' => $admin->user->first_name,
                            'last_name' => $admin->user->last_name,
                        ],
                    );
                    $chat->users()->attach( $record->id, ['role' => 'admin'] );
                }

                $usage = "\n*使用说明*\n设置费率：`设置费率X\.X%`\n设置操作人：`设置操作人 @xxxxx` @xxxx 设置群成员使用。先打空格再打@，会弹出选择更方便。\n删除操作人：`删除操作人 @xxxxx` 先输入“删除操作人” 然后空格，再打@，就出来了选择，这样更方便\n";
                $usage .= "\n*开始记录命令：*`开始`";
                $usage .= "\n*结束记录命令：*`结束`";
                $usage .= "\n*入款命令：*`入款XXX`或`\+XXX`";
                $usage .= "\n*下发命令：*`下发XXX`或`\-XXX`\n";
                $usage .= "\n如果输入错误，可以用 `入款\-XXX` 或 `下发\-XXX`，来修正。";
                $bot->sendMessage([
                    'chat_id' => $chat->id,
                    'text' => "感谢[HIHI](tg://user?id=5192927761)让我加进群。\n".$usage,
                    'parse_mode' => 'MarkdownV2',
                ]);
            } elseif ( $old_status == 'member' && $new_status == 'left' ) {
                $chat = Chat::find($bot_update->chat->id);
                if ( $chat ) {
                    $chat->users()->detach();
                }

                // $this->endWorkShift($chat->id);
            }
        } elseif ( hash_equals('chat_member', $update->objectType()) ) {
            $new_chat_member = $update->chat_member->new_chat_member;
            $status = $new_chat_member->status;

            if ( in_array($status, ['restricted', 'left', 'kicked']) ) {
                $chat = Chat::find($update->chat_member->chat->id);
                if ($chat) {
                    $chat->users()->detach($new_chat_member->user->id);
                }
            } elseif (in_array($status, ['creator', 'administrator'])) {
                $chat = Chat::find($update->chat_member->chat->id);
                if ($chat) {
                    $chat->users()->attach($new_chat_member->user->id, ['role' => 'admin']);
                }
            }
        }
    }

    /**
     * User info handle
     * @param TeleUser $sender
     * @param int $chat_id
     */
    public function senderHandle($sender, $chat_id)
    {
        $key = 'huntkeybot_dummy_grant_' . $chat_id;
        if ( Cache::has($key) && !empty(Cache::get($key)) ) {
            $user = User::firstOrCreate(
                ['id' => $sender->id],
                [
                    'username'   => $sender->get('username'),
                    'first_name' => $sender->get('first_name'),
                    'last_name'  => $sender->get('last_name'),
                ],
            );

            $queues_grant = Cache::get($key);
            if ( in_array($user->username, $queues_grant) ) {
                Chat::find($chat_id)->users()->attach($user->id, ['role' => 'operator']);
                $arr_key = array_search($user->username, $queues_grant);
                if ( $arr_key !== false ) {
                    unset($queues_grant[$arr_key]);
                    Cache::forever($key, $queues_grant);
                }
            }
        }
    }

    /**
     * Start shift
     * @param int $chat_id
     * @param int $user_id
     */
    public function startRecords($chat_id, $user_id)
    {
        if ( $this->isAdmin($chat_id, $user_id) ) {
            $shift = DB::table('shifts')
                ->where('chat_id', $chat_id)
                ->where('is_start', TRUE)
                ->where('is_end', FALSE)
                ->first();

            if ( $shift ) {
                $this->activeBot->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => '机器人已开始记录今天账单。',
                    'reply_to_message_id' => $this->activeMsgId
                ]);
            } else {
                // Create new shift
                Chat::find($chat_id)->shifts()->create(['is_start'  => TRUE]);
                $this->activeBot->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => '开始记录今天账单。',
                    'reply_to_message_id' => $this->activeMsgId
                ]);
            }
        } else {
            $this->activeBot->sendMessage([
                'chat_id' => $chat_id,
                'text' => '你没有权限啦。',
                'reply_to_message_id' => $this->activeMsgId
            ]);
        }
    }

    /**
     * Stop shift
     * @param int $chat_id
     * @param int $user_id
     */
    public function stopRecords($chat_id, $user_id)
    {
        if ( $this->isAdmin($chat_id, $user_id) ) {
            $shift = DB::table('shifts')
                ->where('chat_id', $chat_id)
                ->where('is_start', TRUE)
                ->where('is_end', FALSE)
                ->first();

            if ( $shift ) {
                Shift::find($shift->id)->update(['is_end' => TRUE]);
                $this->activeBot->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => '结束记录。',
                    'reply_to_message_id' => $this->activeMsgId
                ]);
            } else {
                $this->activeBot->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => '记录今天账单还没开始，输入”开始”机器人会开始记录。',
                    'reply_to_message_id' => $this->activeMsgId
                ]);
            }
        } else {
            $this->activeBot->sendMessage([
                'chat_id' => $chat_id,
                'text' => '你没有权限啦。',
                'reply_to_message_id' => $this->activeMsgId
            ]);
        }
    }

    /**
     * Set rate to current shift
     * @param int $chat_id
     * @param int $user_id
     * @param float $rate
     */
    public function rateHandle($chat_id, $user_id, $rate)
    {
        if ( $this->isAdmin($chat_id, $user_id) ) {
            $shift = $this->getCurrentShift($chat_id);

            if ( $shift ) {
                $shift->rate = floatval($rate) > 0 ? floatval($rate) : 1;
                $shift->save();

                $this->activeBot->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => '设置成功！',
                    'reply_to_message_id' => $this->activeMsgId
                ]);
            } else {
                $this->activeBot->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => '记录今天账单还没开始，请输入”开始”机器人会开始记录。',
                    'reply_to_message_id' => $this->activeMsgId
                ]);
            }
        } else {
            $this->activeBot->sendMessage([
                'chat_id' => $chat_id,
                'text' => '你没有权限啦。',
                'reply_to_message_id' => $this->activeMsgId
            ]);
        }
    }

    /**
     * Perform when has new deposit action
     * @param int $chat_id
     * @param int $user_id
     * @param float $amount
     */
    public function depositHandle($chat_id, $user_id, $amount)
    {
        if ( $this->isOperator($chat_id, $user_id) ) {
            $shift = $this->getCurrentShift($chat_id);

            if ( $shift ) {
                DB::table('deposits')->insert([
                    'user_id' => $user_id,
                    'shift_id' => $shift->id,
                    'gross' => $amount,
                    'net' => floatval($amount) * (1 - ($shift->rate / 100)),
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time()),
                ]);

                $this->statisticHandle($shift->id, $chat_id);
            }
        } else {
            $this->activeBot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "你没有权限啦，请跟管理员申请操作人的权限！",
                'reply_to_message_id' => $this->activeMsgId
            ]);
        }
    }

    /**
     * Perform when has new issued action
     * @param int $chat_id
     * @param int $user_id
     * @param float $amount
     */
    public function issuedHandle($chat_id, $user_id, $amount)
    {
        if ( $this->isOperator($chat_id, $user_id) ) {
            $shift = $this->getCurrentShift($chat_id);

            if ( $shift ) {
                DB::table('issueds')->insert([
                    'user_id' => $user_id,
                    'shift_id' => $shift->id,
                    'amount' => $amount,
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time()),
                ]);

                $this->statisticHandle($shift->id, $chat_id);
            }
        } else {
            $this->activeBot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "你没有权限啦，请跟管理员申请操作人的权限！",
                'reply_to_message_id' => $this->activeMsgId
            ]);
        }
    }

    /**
     * Perform statistics when deposit or issued handled
     * @param int $shift_id
     * @param int $chat_id
     */
    public function statisticHandle($shift_id, $chat_id)
    {
        $shift = Shift::find($shift_id);

        if ( $shift ) {
            $deposits = DB::table('deposits')->where('shift_id', $shift->id)
                ->join('users', 'deposits.user_id', '=', 'users.id')
                ->orderByDesc('created_at');
            $issueds = DB::table('issueds')->where('shift_id', $shift->id)
                ->join('users', 'issueds.user_id', '=', 'users.id')
                ->orderByDesc('created_at');

            $content = "*入款（". $deposits->count() ."笔）*\n";
            foreach ( $deposits->take(4)->get() as $deposit ) {
                $created_at = date_create_from_format('Y-m-d H:i:s', $deposit->created_at);
                $created_at = $created_at->format('H:i:s');
                $content .= "`{$deposit->first_name} {$created_at}`：*{$deposit->gross}*\n";
            }
            $content .= "*下发（". $issueds->count() ."笔）*\n";
            foreach ( $issueds->take(4)->get() as $issued ) {
                $created_at = date_create_from_format('Y-m-d H:i:s', $deposit->created_at);
                $created_at = $created_at->format('H:i:s');
                $content .= "`{$issued->first_name} {$created_at}` ：*{$issued->amount}*\n";
            }

            $content .= "*总入款：*". $deposits->sum('gross') ."\n";
            $content .= "*费率：*". $shift->rate ."%\n";
            $content .= "*应下发：*" . $deposits->sum('net') . "\n";
            $content .= "*总下发：*". $issueds->sum('amount') ."\n";
            $content .= "*未下发：*" . ($deposits->sum('net') - $issueds->sum('amount'));

            $content = str_replace([".", "-"], ["\.", "\-"], $content);

            $this->activeBot->sendMessage([
                'chat_id' => $chat_id,
                'text' => $content,
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => (new InlineKeyboardMarkup())
                    ->row(new InlineKeyboardButton([
                        'text' => "📝点击跳转完整账单",
                        'url' => route('telegram.chats.index', ['chat_id' => $chat_id]),
                    ])),
            ]);
        }
    }

    /**
     * Determine if user has admin role
     * @param int $chat_id
     * @param int $user_id
     */
    public function isAdmin($chat_id, $user_id)
    {
        $data = DB::table('chat_user')->where([ ['chat_id', $chat_id], ['user_id', $user_id] ])->first(['role']);
        return (!is_null($data) && $data->role === 'admin') ? TRUE : FALSE;
    }

    /**
     * Determine if user has operator role
     * @param int $chat_id
     * @param int $user_id
     */
    public function isOperator($chat_id, $user_id)
    {
        $data = DB::table('chat_user')->where([ ['chat_id', $chat_id], ['user_id', $user_id] ])->first(['role']);
        return $this->isAdmin($chat_id, $user_id) || (!is_null($data) && $data->role === 'operator') ? TRUE : FALSE;
    }

    /**
     * Grant operator role to user
     * @param int $chat_id
     * @param TeleUser|string $user
     * @param boolean $dummy
     */
    public function grantRoles($chat_id, $user, $dummy = false)
    {
        $chat = Chat::find($chat_id);

        if ( $chat ) {
            if ($dummy) {
                $record = User::where('username', '=', $user)->first();
                if ( $record && ! $this->isAdmin($chat->id, $record->id) ) {

                    $chat->users()->attach($record->id, ['role' => 'operator']);

                } else {
                    $key = 'huntkeybot_dummy_grant_' . $chat->id;
                    if ( Cache::has($key) ) {
                        $value = Cache::get($key);
                        array_push($value, $user);
                        Cache::forever($key, $value);
                    } else {
                        Cache::forever($key, [$user]);
                    }
                }
            } else {
                $record = User::firstOrCreate(
                    ['id' => $user->id],
                    [
                        'username' => $user->get('username'),
                        'first_name' => $user->get('first_name'),
                        'last_name' => $user->get('last_name')
                    ],
                );
                if ( $this->isAdmin($chat->id, $record->id) ) {
                    $this->activeBot->sendMessage([
                        'chat_id' => $chat->id,
                        'text' => "“".$record->first_name."”已经是管理员了，不要授予操作权限！",
                        'reply_to_message_id' => $this->activeMsgId
                    ]);
                } else {
                    $chat->users()->detach($record->id);
                    $chat->users()->attach($record->id, ['role' => 'operator']);
                }
            }
        }
    }

    /**
     * Revoke operator role from user
     * @param int $chat_id
     * @param int|string $user
     * @param boolean $dummy
     */
    public function revokeRoles($chat_id, $user, $dummy = false)
    {
        $chat = Chat::find($chat_id);

        if ( $chat ) {
            if ($dummy) {
                $record = User::where('username', '=', $user)->first();
                if ( $record ) {
                    $chat->users()->detach($record->id);
                } else {
                    $key = 'huntkeybot_dummy_grant_' . $chat->id;
                    if ( Cache::has($key) ) {
                        $value = Cache::get($key);
                        $arr_key = array_search($user, $value);
                        if ( $arr_key !== false ) {
                            unset($value[$arr_key]);
                            Cache::forever($key, $value);
                        }
                    } else {
                        $this->activeBot->sendMessage([
                            'chat_id' => $chat->id,
                            'text' => "“" . $user . "”本来不是操作人，不要删除操作的权限！",
                            'reply_to_message_id' => $this->activeMsgId
                        ]);
                    }
                }
            } else {
                $chat->users()->detach($user);
            }
        }
    }

    /**
     * Get current active shift in chat
     * @param int $chat_id
     */
    public function getCurrentShift($chat_id)
    {
        $shift = Shift::where([
            ['chat_id', $chat_id],
            ['is_start', TRUE],
            ['is_end', FALSE],
        ])->first();
        return is_null($shift) ? null : $shift;
    }

    /**
     * Debug via Telegram bot
     */
    public function reportBug($message)
    {
        Telegram::bot()->sendMessage([
            'chat_id' => 5192927761,
            'text' => $message,
        ]);
    }
}
