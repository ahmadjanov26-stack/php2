<?php

$content = file_get_contents("php://input");
$update = json_decode($content, true);

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? '';

$token = " <?php

$content = file_get_contents("php://input");
$update = json_decode($content, true);

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? '';

$token = "7650243925:AAHpPaA2S3uZD_tx21xQwbiJO2dJnlxs_-0";

if($chat_id){
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=Salom!");
}";

if($chat_id){
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=Salom!");
}
<?php
ob_start();
error_reporting(0);

// ─── SOZLAMALAR ───────────────────────────────────────────
define('API_KEY', '7650243925:AAHpPaA2S3uZD_tx21xQwbiJO2dJnlxs_-0');
$admin   = 5029588103;
$kanali  = "https://t.me/kinomania_fox";
$bot     = "zorkinoborbot";
$admen   = "admin_user"; // Keyinchalik to'ldiring
$reklama = "Bu kino @$bot orqali yuklandi";

// ─── KODLAR BAZASI (fayldan o'qiladi) ─────────────────────
// codes.json formati: {"KOD123": {"file_id": "...", "type": "video", "title": "Film nomi"}}
function loadCodes() {
    if (!file_exists("codes.json")) {
        file_put_contents("codes.json", json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    return json_decode(file_get_contents("codes.json"), true) ?? [];
}
function saveCodes($codes) {
    file_put_contents("codes.json", json_encode($codes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ─── PAPKALAR ─────────────────────────────────────────────
foreach (["step", "stat", "admin"] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);   // BUG 2 tuzatildi
}

// ─── BOT API FUNKSIYASI ───────────────────────────────────
function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        error_log("cURL error: " . curl_error($ch));
        return null;
    }
    return json_decode($res);
}

// ─── UPDATE O'QISH ────────────────────────────────────────
$update   = json_decode(file_get_contents("php://input"));
$message  = $update->message ?? null;
$callback = $update->callback_query ?? null;
$joinReq  = $update->chat_join_request ?? null;

// Message ma'lumotlari
$chat_id    = $message->chat->id ?? null;
$uid        = $message->from->id ?? null;
$text       = $message->text ?? "";
$mid        = $message->message_id ?? null;
$first_name = $message->from->first_name ?? "";
$username   = $message->from->username ?? "";
$type       = $message->chat->type ?? "";
$doc        = $message->document ?? null;
$video      = $message->video ?? null;

// Callback ma'lumotlari
$cqid      = $callback->id ?? null;
$ccid      = $callback->message->chat->id ?? null;
$callmid   = $callback->message->message_id ?? null;
$from_id   = $callback->from->id ?? null;
$cdata     = $callback->data ?? "";

// Join request ma'lumotlari
$joinchatid   = $joinReq->chat->id ?? null;
$qb           = $joinReq->from->id ?? null;
$fjname       = $joinReq->from->first_name ?? "";

// ─── KANAL NOMI OLISH ─────────────────────────────────────
function getName($id) {
    $res = bot('getChat', ['chat_id' => $id])->result ?? null;
    if (!$res) return "Noma'lum";
    return !empty($res->first_name) ? $res->first_name : ($res->title ?? "Noma'lum");
}

// ─── OBUNA TEKSHIRUVI ─────────────────────────────────────
function joinchat($id) {
    global $mid;
    $get = file_get_contents("stat/kanal.txt");
    if (!$get || trim($get) === "" || $get === "0") return true;

    $lines    = array_filter(array_map('trim', explode("\n", $get)));
    $uns      = false;
    $keyboard = [];

    foreach ($lines as $line) {
        if (empty($line)) continue;

        if (strpos($line, "@") !== false) {
            $ex   = explode("@", $line);
            $urlt = "@" . trim($ex[1]);
            $url  = "https://t.me/" . trim($ex[1]);
            $name = getName($urlt);
            $ret  = bot("getChatMember", ["chat_id" => $urlt, "user_id" => $id]);
            $stat = $ret->result->status ?? "left";
        } else {
            $ex   = explode("++", $line);
            $urlt = trim($ex[0]);
            $url  = trim($ex[1] ?? "#");
            $name = getName($urlt);
            $getz = @file_get_contents("stat/$urlt.txt") ?? "";
            $stat = (strpos($getz, (string)$id) !== false) ? "member" : "left";
        }

        $isMember   = in_array($stat, ["creator", "administrator", "member"]);
        $emoji      = $isMember ? "✅" : "❌";
        $keyboard[] = [["text" => "$emoji $name", "url" => $url]];
        if (!$isMember) $uns = true;
    }

    if (!$uns) return true;

    $keyboard[] = [["text" => "🔄 TEKSHIRISH", "callback_data" => "obuna"]];
    bot('sendMessage', [
        'chat_id'                  => $id,
        'reply_to_message_id'      => $mid,
        'text'                     => "<b>🤖 Botdan to'liq foydalanish uchun</b> quyidagi kanallarga obuna bo'ling 👇",
        'parse_mode'               => "html",
        'disable_web_page_preview' => true,
        'reply_markup'             => json_encode(["inline_keyboard" => $keyboard]),
    ]);
    return false;
}

// ─── YANGI A'ZO XABARNOMASI ───────────────────────────────
if ($message) {
    $users       = @file_get_contents("azolar.txt") ?? "";
    $azolar_soni = max(0, substr_count($users, "\n"));
    if (!preg_match("/$chat_id/", $users)) {
        file_put_contents("azolar.txt", $users . "\n$chat_id");
        bot('sendMessage', [
            'chat_id'    => $admin,
            'text'       => "🤖 Botga yangi a'zo\n✅ User: @$username\n🆔 ID: <code>$chat_id</code>\n✉️ Lichka: <a href='tg://user?id=$chat_id'>$first_name</a>\n\n👥 Jami: $azolar_soni ta",
            'parse_mode' => "html",
        ]);
    }
}

// ─── JOIN REQUEST HANDLER ─────────────────────────────────
if ($joinchatid) {
    // Avtomatik tasdiqlash kerak bo'lsa, quyidagi izohni oching:
    // bot("approveChatJoinRequest", ["chat_id" => $joinchatid, "user_id" => $qb]);
    $getz = @file_get_contents("stat/$joinchatid.txt") ?? "";
    if (!preg_match("/$qb/", $getz)) {
        file_put_contents("stat/$joinchatid.txt", "$getz\n$qb");
    }
    exit;
}

// ─── ADMIN YORDAMCHI ─────────────────────────────────────
function isAdmin($id) {
    global $admin;
    return (int)$id === (int)$admin;
}

// ─── /START ───────────────────────────────────────────────
if ($text === "/start" && $chat_id) {
    if (!joinchat($chat_id)) exit;
    bot('sendMessage', [
        'chat_id'      => $chat_id,
        'text'         => "👋 Salom, <b>$first_name</b>!\n\n🎬 Kino yoki video olish uchun maxsus <b>kodni</b> yuboring.",
        'parse_mode'   => "html",
        'reply_markup' => json_encode(["inline_keyboard" => [[["text" => "✉️ Adminga xabar yuborish", "url" => "https://t.me/$bot?start=msg"]]]]),
    ]);
    exit;
}

// ─── /ADMIN PANELI ────────────────────────────────────────
if ($text === "/admin" && $chat_id) {
    if (!isAdmin($uid)) {
        bot('sendMessage', ['chat_id' => $chat_id, 'text' => "⛔ Ruxsat yo'q."]);
        exit;
    }
    $codes   = loadCodes();
    $users_c = max(0, substr_count(@file_get_contents("azolar.txt") ?? "", "\n"));
    $kanal_c = count(array_filter(array_map('trim', explode("\n", @file_get_contents("stat/kanal.txt") ?? ""))));
    $keyboard = ["inline_keyboard" => [
        [["text" => "➕ Kod qo'shish",      "callback_data" => "adm_addcode"]],
        [["text" => "🗑 Kod o'chirish",     "callback_data" => "adm_delcode"]],
        [["text" => "📋 Kodlar ro'yxati",   "callback_data" => "adm_listcodes"]],
        [["text" => "📢 Kanal qo'shish",    "callback_data" => "adm_addchan"]],
        [["text" => "🗑 Kanal o'chirish",   "callback_data" => "adm_delchan"]],
        [["text" => "📋 Kanallar ro'yxati", "callback_data" => "adm_listchan"]],
        [["text" => "📊 Statistika",        "callback_data" => "adm_stat"]],
        [["text" => "📣 Habar yuborish",    "callback_data" => "adm_broadcast"]],
    ]];
    bot('sendMessage', [
        'chat_id'      => $chat_id,
        'text'         => "🛠 <b>Admin Panel</b>\n\n👥 Azolar: <b>$users_c</b>\n🎬 Kodlar: <b>" . count($codes) . "</b>\n📢 Kanallar: <b>$kanal_c</b>",
        'parse_mode'   => "html",
        'reply_markup' => json_encode($keyboard),
    ]);
    exit;
}

// ─── ADMIN: KOD QO'SHISH (fayl → nom → kod jarayoni) ─────
if ($message && isAdmin($uid)) {
    $step_file = "step/{$uid}.txt";
    $step      = trim(@file_get_contents($step_file) ?? "");

    if ($step === "wait_file") {
        $fid = null; $ftype = null;
        if ($video)  { $fid = $video->file_id; $ftype = "video"; }
        elseif ($doc){ $fid = $doc->file_id;   $ftype = "document"; }
        if ($fid) {
            file_put_contents($step_file, "wait_title:$ftype:$fid");
            bot('sendMessage', ['chat_id' => $uid, 'text' => "✅ Fayl qabul qilindi.\n\nEndi <b>nom/sarlavha</b> yuboring:", 'parse_mode' => "html"]);
        }
        exit;
    }
    if (strpos($step, "wait_title:") === 0) {
        $parts = explode(":", $step, 3); $ftype = $parts[1]; $fid = $parts[2];
        file_put_contents($step_file, "wait_code:$ftype:$fid:$text");
        bot('sendMessage', ['chat_id' => $uid, 'text' => "✅ Nom: <b>$text</b>\n\nEndi <b>maxsus kod</b> yuboring:", 'parse_mode' => "html"]);
        exit;
    }
    if (strpos($step, "wait_code:") === 0) {
        $parts = explode(":", $step, 4); $ftype = $parts[1]; $fid = $parts[2]; $title = $parts[3];
        $code  = strtoupper(trim($text));
        $codes = loadCodes(); $codes[$code] = ["file_id" => $fid, "type" => $ftype, "title" => $title]; saveCodes($codes);
        file_put_contents($step_file, "");
        bot('sendMessage', ['chat_id' => $uid, 'text' => "✅ Qo'shildi!\n🔑 Kod: <code>$code</code>\n🎬 Sarlavha: <b>$title</b>", 'parse_mode' => "html"]);
        exit;
    }
    if ($step === "wait_chan") {
        $line = trim($text);
        $kanal_content = trim(@file_get_contents("stat/kanal.txt") ?? "") . "\n$line";
        file_put_contents("stat/kanal.txt", trim($kanal_content));
        file_put_contents($step_file, "");
        bot('sendMessage', ['chat_id' => $uid, 'text' => "✅ Kanal qo'shildi: <code>$line</code>", 'parse_mode' => "html"]);
        exit;
    }
    if ($step === "del_chan") {
        $target = trim($text);
        $kanal  = @file_get_contents("stat/kanal.txt") ?? "";
        $lines  = array_filter(array_map('trim', explode("\n", $kanal)));
        file_put_contents("stat/kanal.txt", implode("\n", array_filter($lines, fn($l) => strpos($l, $target) === false)));
        file_put_contents($step_file, "");
        bot('sendMessage', ['chat_id' => $uid, 'text' => "✅ Kanal o'chirildi."]);
        exit;
    }
    if ($step === "del_code") {
        $code  = strtoupper(trim($text)); $codes = loadCodes();
        if (isset($codes[$code])) { unset($codes[$code]); saveCodes($codes); bot('sendMessage', ['chat_id' => $uid, 'text' => "✅ Kod o'chirildi: <code>$code</code>", 'parse_mode' => "html"]); }
        else { bot('sendMessage', ['chat_id' => $uid, 'text' => "❌ Bunday kod topilmadi."]); }
        file_put_contents($step_file, "");
        exit;
    }
    if ($step === "wait_broadcast") {
        $users = @file_get_contents("azolar.txt") ?? "";
        $ids   = array_filter(array_map('trim', explode("\n", $users)));
        $sent  = 0; $fail = 0;
        foreach ($ids as $uid_b) {
            if (empty($uid_b)) continue;
            $res = bot('sendMessage', ['chat_id' => $uid_b, 'text' => $text, 'parse_mode' => "html"]);
            ($res && isset($res->ok) && $res->ok) ? $sent++ : $fail++;
        }
        file_put_contents($step_file, "");
        bot('sendMessage', ['chat_id' => $uid, 'text' => "✅ Habar yuborildi!\n\n✔️ Muvaffaqiyatli: <b>$sent</b>\n❌ Xatolik: <b>$fail</b>", 'parse_mode' => "html"]);
        exit;
    }
}

// ─── CALLBACK QUERY ───────────────────────────────────────
if ($callback && $cqid) {
    bot('answerCallbackQuery', ['callback_query_id' => $cqid]);

    if ($cdata === "obuna") {
        if (joinchat($ccid)) {
            bot('editMessageText', ['chat_id' => $ccid, 'message_id' => $callmid, 'text' => "✅ Tasdiqlandi! Kodni yuboring."]);
        } else {
            bot('answerCallbackQuery', ['callback_query_id' => $cqid, 'text' => "❌ Hali obuna bo'lmadingiz!", 'show_alert' => true]);
        }
        exit;
    }

    if (!isAdmin($from_id)) exit;
    $step_file = "step/{$from_id}.txt";

    $adm_keyboard = ["inline_keyboard" => [
        [["text" => "➕ Kod qo'shish","callback_data"=>"adm_addcode"]],
        [["text" => "🗑 Kod o'chirish","callback_data"=>"adm_delcode"]],
        [["text" => "📋 Kodlar ro'yxati","callback_data"=>"adm_listcodes"]],
        [["text" => "📢 Kanal qo'shish","callback_data"=>"adm_addchan"]],
        [["text" => "🗑 Kanal o'chirish","callback_data"=>"adm_delchan"]],
        [["text" => "📋 Kanallar ro'yxati","callback_data"=>"adm_listchan"]],
        [["text" => "📊 Statistika","callback_data"=>"adm_stat"]],
        [["text" => "📣 Habar yuborish","callback_data"=>"adm_broadcast"]],
    ]];
    $back_btn = ["inline_keyboard" => [[["text" => "🔙 Orqaga", "callback_data" => "adm_back"]]]];

    if ($cdata === "adm_addcode") {
        file_put_contents($step_file, "wait_file");
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>"➕ <b>Yangi kod qo'shish</b>\n\nVideo yoki faylni yuboring:","parse_mode"=>"html"]);
    } elseif ($cdata === "adm_listcodes") {
        $codes = loadCodes();
        $t = empty($codes) ? "Kodlar yo'q." : "📋 <b>Kodlar:</b>\n\n" . implode("\n", array_map(fn($k,$v)=>"• <code>$k</code> — {$v['title']}", array_keys($codes), $codes));
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>$t,'parse_mode'=>"html",'reply_markup'=>json_encode($back_btn)]);
    } elseif ($cdata === "adm_delcode") {
        file_put_contents($step_file, "del_code");
        $codes = loadCodes();
        $list  = empty($codes) ? "Kodlar yo'q." : implode("\n", array_map(fn($k)=>"• <code>$k</code>", array_keys($codes)));
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>"🗑 O'chirish uchun kodni yuboring:\n\n$list",'parse_mode'=>"html"]);
    } elseif ($cdata === "adm_addchan") {
        file_put_contents($step_file, "wait_chan");
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>"📢 Kanal qo'shish:\n\n<b>Ochiq:</b> <code>@username</code>\n<b>Yopiq:</b> <code>-100ID++https://t.me/+link</code>\n\nYuboring:",'parse_mode'=>"html"]);
    } elseif ($cdata === "adm_listchan") {
        $lines = array_filter(array_map('trim', explode("\n", @file_get_contents("stat/kanal.txt") ?? "")));
        $t = empty($lines) ? "Kanallar yo'q." : "📋 <b>Kanallar:</b>\n\n" . implode("\n", array_map(fn($l)=>"• <code>$l</code>", $lines));
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>$t,'parse_mode'=>"html",'reply_markup'=>json_encode($back_btn)]);
    } elseif ($cdata === "adm_delchan") {
        file_put_contents($step_file, "del_chan");
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>"🗑 O'chirish uchun kanal ID yoki @username yuboring:"]);
    } elseif ($cdata === "adm_stat") {
        $u = max(0, substr_count(@file_get_contents("azolar.txt")??"","\n"));
        $c = count(loadCodes());
        $k = count(array_filter(array_map('trim', explode("\n", @file_get_contents("stat/kanal.txt")??""))) );
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>"📊 <b>Statistika</b>\n\n👥 Foydalanuvchilar: <b>$u</b>\n🎬 Kodlar: <b>$c</b>\n📢 Kanallar: <b>$k</b>",'parse_mode'=>"html",'reply_markup'=>json_encode($back_btn)]);
    } elseif ($cdata === "adm_broadcast") {
        file_put_contents($step_file, "wait_broadcast");
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>"📣 <b>Habar yuborish</b>\n\nBarcha foydalanuvchilarga yuboriladigan xabarni yozing:\n\n<i>(HTML teglari ishlaydi: &lt;b&gt;, &lt;i&gt;, &lt;code&gt;)</i>",'parse_mode'=>"html",'reply_markup'=>json_encode($back_btn)]);
    } elseif ($cdata === "adm_back") {
        file_put_contents($step_file, "");
        $u = max(0, substr_count(@file_get_contents("azolar.txt")??"","\n"));
        bot('editMessageText', ['chat_id'=>$ccid,'message_id'=>$callmid,'text'=>"🛠 <b>Admin Panel</b>\n\n👥 Azolar: <b>$u</b>\n🎬 Kodlar: <b>".count(loadCodes())."</b>",'parse_mode'=>"html",'reply_markup'=>json_encode($adm_keyboard)]);
    }
    exit;
}

// ─── MAXSUS KOD → VIDEO YUBORISH (SAVE RESTRICT) ─────────
if ($message && $text && $chat_id && !str_starts_with($text, "/")) {
    if (!joinchat($chat_id)) exit;

    $code  = strtoupper(trim($text));
    $codes = loadCodes();

    if (!isset($codes[$code])) {
        bot('sendMessage', [
            'chat_id'      => $chat_id,
            'text'         => "❌ Bunday kod topilmadi.\n\nTo'g'ri kodni kiriting yoki admin bilan bog'laning 👇",
            'reply_markup' => json_encode(["inline_keyboard" => [[["text" => "👤 Admin bilan bog'lanish", "url" => "https://t.me/$admen"]]]]),
        ]);
        exit;
    }

    $item    = $codes[$code];
    $file_id = $item['file_id'];
    $ftype   = $item['type'];
    $title   = $item['title'];
    $caption = "🎬 <b>$title</b>\n\n<i>$reklama</i>\n\n🔒 Kontent himoyalangan.";

    $payload = [
        'chat_id'         => $chat_id,
        'caption'         => $caption,
        'parse_mode'      => "html",
        'protect_content' => true,    // ← SAVE RESTRICT (forward/saqlash taqiqlangan)
    ];

    if ($ftype === "video")         { $payload['video']    = $file_id; bot('sendVideo',    $payload); }
    elseif ($ftype === "document")  { $payload['document'] = $file_id; bot('sendDocument', $payload); }
    elseif ($ftype === "photo")     { $payload['photo']    = $file_id; bot('sendPhoto',    $payload); }
    else                            { $payload['video']    = $file_id; bot('sendVideo',    $payload); }
    exit;
}