<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram;
use App\Models\Chat;
use App\Models\User;
use App\Models\Shift;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Objects\Keyboard\InlineKeyboardButton;
use Telegram\Bot\Objects\Keyboard\InlineKeyboardMarkup;
use Telegram\Bot\Objects\User as TeleUser;
use Telegram\Bot\Objects\Chat as TeleChat;
use App\Exports\MultiSheetExport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

date_default_timezone_set('Asia/Manila');

class HuntKeyBotController extends Controller
{
    protected $activeBot;
    protected $activeMsgId;
    protected $activeChatId;
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
            $chat = $message->get('chat');
            $from = $message->get('from');
            $text = $message->get('text');
            $entities = $message->get('entities');

            $this->activeChatId = $chat->id;

            if ( $message->has('entities') && str_starts_with($text, '/') ) {
                foreach ( $entities as $entity ) {
                    if ( $entity->type == 'bot_command' ) {
                        $cmd = mb_substr( $text, $entity->offset, $entity->length, 'UTF-8' );
                        $cmd = ltrim($cmd, "/");
                        if ( method_exists(get_class($this), $cmd) && is_callable(array(get_class($this), $cmd)) ) {
                            $this->$cmd(new TeleChat($chat), $text);
                        }
                    }
                }
                return;
            }

            $this->activeMsgId = $message->get('message_id');
            $this->senderHandle(new TeleUser($from), $chat->id);

            foreach ( config('enums.triggers') as $key => $trigger ) {

                $isMatch = preg_match($trigger, $text, $matches);

                if ( $isMatch == 1 ) {

                    $callbackFunc = config('enums.callback')[$key];
                    $this->$callbackFunc($chat->id, $from->id, $matches, $entities, $text);

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

                $usage = "\n*使用说明*\n设置费率：`设置费率X\.X%`\n设置操作人：`设置操作人 @xxxxx` @xxxx 设置群成员使用。先打空格再打@，会弹出选择更方便，可能加多个。\n删除操作人：`删除操作人 @xxxxx` 先输入“删除操作人” 然后空格，再打@，就出来了选择，这样更方便，可能删除多个。\n";
                $usage .= "\n*开始记录命令：*`开始`";
                $usage .= "\n*结束记录命令：*`结束`";
                $usage .= "\n*入款命令：*`入款XXX`或`\+XXX`";
                $usage .= "\n*下发命令：*`下发XXX`或`\-XXX`";
                $usage .= "\n*导出命令：*`/export`比如`/export 2022-2-22` 或 `/export`\n";
                $usage .= "\n如果输入错误，可以用 `入款\-XXX` 或 `下发\-XXX`，来修正。";
                $bot->sendMessage([
                    'chat_id' => $chat->id,
                    'text' => "感谢[". $bot_update->from->first_name ."](tg://user?id=". $bot_update->from->id .")让我加进群。\n".$usage,
                    'parse_mode' => 'MarkdownV2',
                ]);
            } elseif ( $old_status == 'member' && $new_status == 'left' ) {
                $chat = Chat::find($bot_update->chat->id);
                if ( $chat ) {
                    $chat->users()->detach();
                    $shift = $this->getCurrentShift($chat->id);
                    if ( $shift ) {
                        $shift->is_end = TRUE;
                        $shift->save();
                    }
                }
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

    public function start($chat, $text = null)
    {
        $usage = "\n*使用说明*\n设置费率：`设置费率X\.X%`\n设置操作人：`设置操作人 @xxxxx` @xxxx 设置群成员使用。先打空格再打@，会弹出选择更方便，可能加多个。\n删除操作人：`删除操作人 @xxxxx` 先输入“删除操作人” 然后空格，再打@，就出来了选择，这样更方便，可能删除多个。\n";
        $usage .= "\n*开始记录命令：*`开始`";
        $usage .= "\n*结束记录命令：*`结束`";
        $usage .= "\n*入款命令：*`入款XXX`或`\+XXX`";
        $usage .= "\n*下发命令：*`下发XXX`或`\-XXX`";
        $usage .= "\n*导出命令：*`/export`比如`/export 2022-2-22` 或 `/export`\n";
        $usage .= "\n如果输入错误，可以用 `入款\-XXX` 或 `下发\-XXX`，来修正。";

        $this->activeBot->sendMessage([
            'chat_id' => $chat->id,
            'text' => $usage,
            'parse_mode' => 'MarkdownV2',
        ]);
    }

    public function reload($chat_obj, $text = null)
    {
        $chat = Chat::updateOrCreate(
            ['id' => $chat_obj->id],
            [
                'type' => $chat_obj->get('type'),
                'title' => $chat_obj->get('title'),
                'username' => $chat_obj->get('username'),
            ],
        );

        $admins = $this->activeBot->getChatAdministrators(['chat_id' => $chat->id]);

        foreach ($admins as $admin) {
            $record = User::updateOrCreate(
                ['id' => $admin->user->id],
                [
                    'username' => $admin->user->username,
                    'first_name' => $admin->user->first_name,
                    'last_name' => $admin->user->last_name,
                ],
            );
            $chat->users()->detach( $record->id );
            $chat->users()->attach( $record->id, ['role' => 'admin'] );
        }

        Cache::flush();

        $this->start($chat_obj);
    }

    public function export($chat_obj, $text = null)
    {
        $date = date('Y-m-d');
        $isMatch = preg_match('/^\/export\s*(?P<date>\d{4}-\d{1,2}-\d{1,2})/', $text, $matches);
        if ( $isMatch == 1 ) {
            $date = date_create_from_format('Y-m-d', $matches['date'])->format('Y-m-d');
        }

        try {
            $chat = Chat::findOrFail($chat_obj->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->reportBug($e->getMessage());
            return;
        }

        $file_name = $chat->title . date_create_from_format('Y-m-d', $date)->format('Ymd') . '.xlsx';

        $res = $this->activeBot->sendMessage([
            'chat_id' => $chat->id,
            'text' => '数据处理中，请稍等！'
        ]);

        Excel::store(new MultiSheetExport($date, $chat->id), $file_name, 'local');

        if ( Storage::disk('local')->exists($file_name) ) {
            $document = fopen(Storage::path($file_name), 'rb');
            $this->activeBot->sendDocument([
                'chat_id' => $chat->id,
                'document' => $document,
                'caption' => $chat->title . '完整账单',
            ]);

            $this->activeBot->deleteMessage([
                'chat_id' => $chat->id,
                'message_id' => $res->message_id
            ]);

            Storage::disk('local')->delete($file_name);
        } else {
            $this->activeBot->sendMessage([
                'chat_id' => $chat->id,
                'text' => '导出失败，请使用网页版再导出！'
            ]);
        }
    }

    /**
     * User info handle
     * @param TeleUser $sender
     * @param int $chat_id
     */
    public function senderHandle($sender, $chat_id)
    {
        try {
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
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
        }
    }

    /**
     * Start shift
     * @param int $chat_id
     * @param int $user_id
     */
    public function startRecords($chat_id, $user_id, ...$param)
    {
        try {
            if ( $this->isAdmin($chat_id, $user_id) ) {
                $shift = DB::table('shifts')->where('chat_id', $chat_id)->where('is_start', TRUE)->where('is_end', FALSE)->latest()->first();
    
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
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
        }
    }

    /**
     * Stop shift
     * @param int $chat_id
     * @param int $user_id
     */
    public function stopRecords($chat_id, $user_id, ...$param)
    {
        try {
            if ( $this->isAdmin($chat_id, $user_id) ) {
                $affected_row = DB::table('shifts')->where('chat_id', $chat_id)->where('is_start', TRUE)->where('is_end', FALSE)->update(['is_end' => TRUE]);
    
                if ( $affected_row > 0 ) {
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
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
        }
    }

    /**
     * Set rate to current shift
     * @param int $chat_id
     * @param int $user_id
     * @param float $rate
     */
    public function rateHandle($chat_id, $user_id, $matches, ...$param)
    {
        try {
            if ( $this->isAdmin($chat_id, $user_id) ) {
                $shift = $this->getCurrentShift($chat_id);
    
                if ( $shift ) {
                    $shift->rate = floatval($matches['rate']);
                    $shift->save();
    
                    $this->activeBot->sendMessage([
                        'chat_id' => $chat_id,
                        'text' => '设置成功！费率是' . $shift->rate,
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
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
        }
    }

    /**
     * Perform when has new deposit action
     * @param int $chat_id
     * @param int $user_id
     * @param float $amount
     */
    public function depositHandle($chat_id, $user_id, $matches, ...$param)
    {
        try {
            if ( $this->isOperator($chat_id, $user_id) ) {
                $shift = $this->getCurrentShift($chat_id);
    
                if ( $shift ) {
                    DB::table('deposits')->insert([
                        'user_id' => $user_id,
                        'shift_id' => $shift->id,
                        'gross' => $matches['amount'],
                        'net' => floatval($matches['amount']) * (1 - ($shift->rate / 100)),
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
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
        }
    }

    /**
     * Perform when has new issued action
     * @param int $chat_id
     * @param int $user_id
     * @param float $amount
     */
    public function issuedHandle($chat_id, $user_id, $matches, ...$param)
    {
        try {
            if ( $this->isOperator($chat_id, $user_id) ) {
                $shift = $this->getCurrentShift($chat_id);
    
                if ( $shift ) {
                    DB::table('issueds')->insert([
                        'user_id' => $user_id,
                        'shift_id' => $shift->id,
                        'amount' => $matches['amount'],
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
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
        }
    }

    /**
     * Perform statistics when deposit or issued handled
     * @param int $shift_id
     * @param int $chat_id
     */
    public function statisticHandle($shift_id, $chat_id)
    {
        try {
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
                    $created_at = date_create_from_format('Y-m-d H:i:s', $issued->created_at);
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
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
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
     * @param int $user_id
     * @param mix|null $matches
     * @param mix $entities
     * @param string $text
     */
    public function grantRoles($chat_id, $user_id, $matches = null, $entities, $text)
    {
        try {
            $chat = Chat::findOrFail($chat_id);
    
            if ( $this->isAdmin($chat_id, $user_id) ) {
    
                foreach ($entities as $entity) {
                    if ( hash_equals('text_mention', $entity->type) ) {
    
                        $user = new TeleUser($entity->user);
                        $operator = User::firstOrCreate(
                            ['id' => $user->get('id')],
                            [
                                'first_name' => $user->get('first_name'),
                                'last_name' => $user->get('last_name'),
                            ],
                        );
                        if ( $this->isAdmin($chat->id, $operator->id) ) {
                            continue;
                        }
    
                        $chat->users()->detach($operator->id);
                        $chat->users()->attach($operator->id, ['role' => 'operator']);
    
                    } elseif ( hash_equals('mention', $entity->type) ) {
                        $username = mb_substr( $text, $entity->offset, $entity->length, 'UTF-8' );
                        $username = ltrim($username, '@');
    
                        $key = 'huntkeybot_dummy_grant_' . $chat->id;
                        Cache::put('huntkey_username', ['key' => $key, 'value' => $username]);
    
                        $operator = User::where('username', '=', $username)->firstOr(function() {
                            $data = Cache::get('huntkey_username');
                            if ( Cache::has($data['key']) ) {

                                $grant_dummy = Cache::get($data['key']);
                                array_push($grant_dummy, $data['value']);
                                Cache::forever($data['key'], $grant_dummy);
    
                            } else {
                                Cache::forever($data['key'], [$data['value']]);
                            }
                            return array();
                        });
    
                        Cache::forget('huntkey_username');
    
                        if ( $operator ) {
                            if ( $this->isAdmin($chat->id, $operator->id) ) {
                                continue;
                            }
        
                            $chat->users()->detach($operator->id);
                            $chat->users()->attach($operator->id, ['role' => 'operator']);
                        }
                    }
                }
    
                $this->activeBot->sendMessage([
                    'chat_id' => $chat->id,
                    'text' => '设置操作人成功！',
                    'reply_to_message_id' => $this->activeMsgId,
                ]);
    
            } else {
                $this->activeBot->sendMessage([
                    'chat_id' => $chat->id,
                    'text' => '您没有权限啦。',
                    'reply_to_message_id' => $this->activeMsgId,
                ]);
            }
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
        }
    }

    /**
     * Revoke operator role from user
     * @param int $chat_id
     * @param int|string $user
     * @param mix|null $matches
     * @param mix $entities
     * @param string $text
     */
    public function revokeRoles($chat_id, $user_id, $matches = null, $entities, $text)
    {
        try {
            $chat = Chat::findOrFail($chat_id);
    
            if ( $this->isAdmin($chat->id, $user_id) ) {
    
                $admins_count = DB::table('chat_user')->where('chat_id', $chat->id)->where('role', 'admin')->count();
    
                foreach ($entities as $entity) {
                    if ( hash_equals('text_mention', $entity->type) ) {
    
                        $user = new TeleUser($entity->user);
                        $operator = User::firstOrCreate(
                            ['id' => $user->get('id')],
                            [
                                'first_name' => $user->get('first_name'),
                                'last_name' => $user->get('last_name'),
                            ],
                        );
    
                        if ( $this->isAdmin($chat->id, $operator->id) && $admins_count == 1 ) {
                            continue;
                        }
    
                        $chat->users()->detach($operator->id);
    
                    } elseif ( hash_equals('mention', $entity->type) ) {
                        
                        $username = mb_substr( $text, $entity->offset, $entity->length, 'UTF-8' );
                        $username = ltrim($username, '@');
    
                        $key = 'huntkeybot_dummy_grant_' . $chat->id;
                        Cache::put('huntkey_username', ['key' => $key, 'value' => $username]);
    
                        $operator = User::where('username', '=', $username)->firstOr(function() {
                            $data = Cache::get('huntkey_username');
    
                            if ( Cache::has($data['key']) ) {
                                $grant_dummy = Cache::get($data['key']);
                                $arr_key = array_search($data['value'], $grant_dummy);
                                
                                if ( $arr_key !== false ) {
                                    unset($grant_dummy[$arr_key]);
                                    Cache::forever($data['key'], $grant_dummy);
                                }
                            }
    
                            return array();
                        });
    
                        Cache::forget('huntkey_username');
    
                        if ( $operator ) {
                            if ( $this->isAdmin($chat->id, $operator->id) && $admins_count == 1 ) {
                                continue;
                            }
    
                            $chat->users()->detach($operator->id);
                        }
                    }
                }
    
                $this->activeBot->sendMessage([
                    'chat_id' => $chat->id,
                    'text' => '删除操作人成功！',
                    'reply_to_message_id' => $this->activeMsgId,
                ]);
    
            } else {
                $this->activeBot->sendMessage([
                    'chat_id' => $chat->id,
                    'text' => '您没有权限啦。',
                    'reply_to_message_id' => $this->activeMsgId,
                ]);
            }
        } catch (Exception $e) {
            $this->reportBug("Line: ".$e->getLine()."\nMessage: ".$e->getMessage());
        }
    }

    /**
     * Get current active shift in chat
     * @param int $chat_id
     */
    public function getCurrentShift($chat_id)
    {
        $shift = Shift::where('chat_id', $chat_id)->where('is_start', TRUE)->where('is_end', FALSE)->latest()->first();
        return $shift;
    }

    /**
     * Debug via Telegram bot
     */
    public function reportBug($message)
    {
        Telegram::bot()->sendMessage([
            'chat_id' => $this->activeChatId,
            'text' => $message,
        ]);
    }
}
