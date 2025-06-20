<?php
if (!defined('DEFAULT_LANG')) {
    define('DEFAULT_LANG', 'en');
}

if (!class_exists('Config')) {
    class Config
    {
        public static function detectLanguage(): string
        {
            $al = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            if (stripos($al, 'ja') !== false) {
                return 'jp';
            }
            if (stripos($al, 'th') !== false) {
                return 'th-TH';
            }
            if (stripos($al, 'zh-CN') !== false) {
                return 'zh-CN';
            }
            if (stripos($al, 'zh-TW') !== false || stripos($al, 'zh-Hant') !== false) {
                return 'zh-TW';
            }

            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (stripos($ua, 'ja') !== false) {
                return 'jp';
            }
            if (stripos($ua, 'th') !== false) {
                return 'th-TH';
            }
            if (stripos($ua, 'zh-CN') !== false) {
                return 'zh-CN';
            }
            if (stripos($ua, 'zh-TW') !== false || stripos($ua, 'zh-Hant') !== false) {
                return 'zh-TW';
            }

            return DEFAULT_LANG;
        }

        public static function settings(): array
        {
            return [
                'en' => [
                    'title'               => 'Gathron!',
                    'twitter_site'        => '@gathron_en',
                    'twitter_title'       => 'Ephemeral group chat, Gathron!',
                    'twitter_description' => 'Hey everyone! Let’s have some fun in a 15-minute group chat!',
                    'twitter_image'       => '/assets/img/x_card_en.jpg',
                    'og_title'            => 'Gathron!',
                    'og_image'            => '/assets/img/x_card_en.jpg',
                    'og_site_name'        => 'Gathron!',
                    'og_description'      => 'Ephemeral group chat, Gathron!',
                    'copy_success'        => 'URL copied!',
                    'copy_error'          => 'Failed to copy URL.',
                    'hero_text'           => 'Ephemeral group chat',
                    'hero_button'         => 'Start group chat 🫡',
                    'message_input'       => 'Enter a message…',
                    'terms_of_service_1'  => 'By using this service, you agree to the ',
                    'terms_of_service_2'  => 'Terms of Service',
                    'terms_of_service_3'  => '.',
                    'kick_out'            => 'You have been forc...ted to the homepage in 5 seconds. All content will be deleted.',
                    'time_out'            => 'The chat time l...ted to the homepage in 5 seconds. All content will be deleted.',
                    'status_label'        => 'Status',
                    'label_public'        => 'Public',
                    'label_private'       => 'Private',
                    'action_open_room'    => 'Make room public',
                    'action_close_room'   => 'Make room private',
                    'open_room'           => 'Room is now public',
                    'close_room'          => 'Room is now private',
                    'toggle_error'        => 'Failed to change state. Please try again.',
                    'public_rooms_title'  => 'Public Room ID List',
                    'no_public_rooms'     => 'No public rooms',
                    'label_room_id'       => 'Room ID',
                    'lang_code'           => 'en',
                    'image_size_limit_message' => 'Please select an image under 5MB',
                    'image_upload_failed'      => 'Image upload failed',
                    'mask_activated'           => ' activated masking.',
                    'share_message' => "Gathron! chat has been shared 😁\nJoin the conversation 🤪\n#gathron #hashchat #chat #ephemeral\n ",
                    'btn_share'     => 'Share',
                    'share_label'   => 'Share',
                ],
                'jp' => [
                    'title'               => 'Gathron!',
                    'twitter_site'        => '@gathron_jp',
                    'twitter_title'       => '記録が残らないグループチャット、ギャザロン！',
                    'twitter_description' => 'みんな集まれ！15分間だけのグループチャットで楽しもう！',
                    'twitter_image'       => '/assets/img/x_card_jp.jpg',
                    'og_title'            => 'ギャザロン！',
                    'og_image'            => '/assets/img/x_card_jp.jpg',
                    'og_site_name'        => 'ギャザロン！',
                    'og_description'      => '記録が残らないグループチャット、ギャザロン！',
                    'copy_success'        => 'URLをコピーしました',
                    'copy_error'          => 'コピーに失敗しました',
                    'hero_text'           => '記録が残らないグループチャット',
                    'hero_button'         => 'グルチャを始める 🫡',
                    'message_input'       => 'メッセージを入力…',
                    'terms_of_service_1'  => 'ご利用いただくことで',
                    'terms_of_service_2'  => '利用規約',
                    'terms_of_service_3'  => 'に同意したとみなされます。',
                    'kick_out'            => 'このグループチャットは参加者により強制閉鎖されました。5秒後にトップページへリダイレクトします。内容は全て削除されます。',
                    'time_out'            => 'チャットの制限時間が終了しました。5秒後にトップページへリダイレクトします。内容は全て削除されます。',
                    'status_label'        => 'Status',
                    'label_public'        => 'Public',
                    'label_private'       => 'Private',
                    'action_open_room'    => 'ルームを公開',
                    'action_close_room'   => 'ルームを非公開',
                    'open_room'           => 'ルームを公開しました',
                    'close_room'          => 'ルームを非公開にしました',
                    'toggle_error'        => '状態を変更できませんでした。再度お試しください。',
                    'public_rooms_title'  => '公開ルーム一覧',
                    'no_public_rooms'     => '公開中のルームはありません',
                    'label_room_id'       => 'Room ID',
                    'lang_code'           => 'ja',
                    'image_size_limit_message' => '5MB 以下の画像を選択してください',
                    'image_upload_failed'      => '画像アップロードに失敗しました',
                    'mask_activated'           => ' が伏せ文字を発動しました。',
                    'share_message' => "Gathron!チャットが共有されました😁\n会話に参加しましょう🤪\n#gathron #hashchat #chat #ephemeral\n ",
                    'btn_share'     => 'Share',
                    'share_label'   => 'Share',
                ],
                'th-TH' => [
                    'title'                    => 'Gathron!',
                    'twitter_site'             => '@gathron_th',
                    'twitter_title'            => 'แชทกลุ่มชั่วคราว Gathron!',
                    'twitter_description'      => 'มาแชทกลุ่มแบบ 15 นาทีด้วยกันเถอะ!',
                    'twitter_image'            => '/assets/img/x_card_th.jpg',
                    'og_title'                 => 'Gathron!',
                    'og_image'                 => '/assets/img/x_card_th.jpg',
                    'og_site_name'             => 'Gathron!',
                    'og_description'           => 'แชทกลุ่มชั่วคราว Gathron!',
                    'copy_success'             => 'คัดลอก URL แล้ว!',
                    'copy_error'               => 'ไม่สามารถคัดลอก URL ได้',
                    'hero_text'                => 'แชทกลุ่มชั่วคราว',
                    'hero_button'              => 'เริ่มแชท 🫡',
                    'message_input'            => 'พิมพ์ข้อความ…',
                    'terms_of_service_1'       => 'โดยการใช้บริการนี้',
                    'terms_of_service_2'       => 'ข้อกำหนดการให้บริการ',
                    'terms_of_service_3'       => '',
                    'kick_out'                 => 'ห้องนี้ถูกปิดโดยผู้ใช้ โปรดกลับไปที่หน้าแรกภายใน 5 วินาที ข้อมูลทั้งหมดจะถูกลบ',
                    'time_out'                 => 'เวลากลุ่มแชทหมดลง โปรดกลับไปหน้าแรกภายใน 5 วินาที ข้อมูลทั้งหมดจะถูกลบ',
                    'status_label'             => 'Status',
                    'label_public'             => 'Public',
                    'label_private'            => 'Private',
                    'action_open_room'         => 'แชร์ห้อง',
                    'action_close_room'        => 'ปิดห้อง',
                    'open_room'                => 'แชร์ห้องแล้ว',
                    'close_room'               => 'ปิดห้องแล้ว',
                    'toggle_error'             => 'เปลี่ยนสถานะไม่สำเร็จ โปรดลองใหม่',
                    'public_rooms_title'       => 'รายชื่อห้องสาธารณะ',
                    'no_public_rooms'          => 'ไม่มีห้องสาธารณะ',
                    'label_room_id'            => 'Room ID',
                    'lang_code'                => 'th',
                    'image_size_limit_message' => 'โปรดเลือกภาพขนาดไม่เกิน 5MB',
                    'image_upload_failed'      => 'การอัปโหลดภาพล้มเหลว',
                    'mask_activated'           => ' เปิดใช้งานการซ่อนข้อความแล้ว',
                    'share_message'            => "Gathron! แชทถูกแชร์แล้ว 😁\nเข้าร่วมการสนทนา 🤪\n#gathron #hashchat #chat #ephemeral\n ",
                    'btn_share'                => 'Share',
                    'share_label'              => 'Share',
                ],
                'zh-CN' => [
                    'title'                    => 'Gathron!',
                    'twitter_site'             => '@gathron_cn',
                    'twitter_title'            => '临时群聊 Gathron!',
                    'twitter_description'      => '来一起玩 15 分钟的群聊吧！',
                    'twitter_image'            => '/assets/img/x_card_cn.jpg',
                    'og_title'                 => 'Gathron!',
                    'og_image'                 => '/assets/img/x_card_cn.jpg',
                    'og_site_name'             => 'Gathron!',
                    'og_description'           => '临时群聊 Gathron!',
                    'copy_success'             => '已复制链接！',
                    'copy_error'               => '复制链接失败',
                    'hero_text'                => '临时群聊',
                    'hero_button'              => '开始聊天 🫡',
                    'message_input'            => '输入消息…',
                    'terms_of_service_1'       => '使用即表示同意',
                    'terms_of_service_2'       => '服务条款',
                    'terms_of_service_3'       => '',
                    'kick_out'                 => '此群聊已被关闭。5 秒后将重定向到主页，所有内容将被删除。',
                    'time_out'                 => '群聊时间已结束。5 秒后将重定向到主页，所有内容将被删除。',
                    'status_label'             => 'Status',
                    'label_public'             => 'Public',
                    'label_private'            => 'Private',
                    'action_open_room'         => '公开房间',
                    'action_close_room'        => '关闭房间',
                    'open_room'                => '房间已公开',
                    'close_room'               => '房间已关闭',
                    'toggle_error'             => '无法更改状态，请重试',
                    'public_rooms_title'       => '公开房间列表',
                    'no_public_rooms'          => '暂无公开房间',
                    'label_room_id'            => 'Room ID',
                    'lang_code'                => 'zh-CN',
                    'image_size_limit_message' => '请选择小于5MB的图片',
                    'image_upload_failed'      => '图片上传失败',
                    'mask_activated'           => ' 已启用屏蔽模式。',
                    'share_message'            => "Gathron! 聊天已分享 😁\n加入对话吧 🤪\n#gathron #hashchat #chat #ephemeral\n ",
                    'btn_share'                => 'Share',
                    'share_label'              => 'Share',
                ],
                'zh-TW' => [
                    'title'                    => 'Gathron!',
                    'twitter_site'             => '@gathron_tw',
                    'twitter_title'            => '臨時群聊 Gathron!',
                    'twitter_description'      => '來一起享受 15 分鐘的群聊吧！',
                    'twitter_image'            => '/assets/img/x_card_tw.jpg',
                    'og_title'                 => 'Gathron!',
                    'og_image'                 => '/assets/img/x_card_tw.jpg',
                    'og_site_name'             => 'Gathron!',
                    'og_description'           => '臨時群聊 Gathron!',
                    'copy_success'             => '已複製連結！',
                    'copy_error'               => '複製連結失敗',
                    'hero_text'                => '臨時群聊',
                    'hero_button'              => '開始聊天 🫡',
                    'message_input'            => '輸入訊息…',
                    'terms_of_service_1'       => '使用即表示同意',
                    'terms_of_service_2'       => '服務條款',
                    'terms_of_service_3'       => '',
                    'kick_out'                 => '此群聊已被關閉。5 秒後將重定向到首頁，所有內容將被刪除。',
                    'time_out'                 => '群聊時間已結束。5 秒後將重定向到首頁，所有內容將被刪除。',
                    'status_label'             => 'Status',
                    'label_public'             => 'Public',
                    'label_private'            => 'Private',
                    'action_open_room'         => '公開房間',
                    'action_close_room'        => '關閉房間',
                    'open_room'                => '房間已公開',
                    'close_room'               => '房間已關閉',
                    'toggle_error'             => '無法更改狀態，請重試',
                    'public_rooms_title'       => '公開房間列表',
                    'no_public_rooms'          => '目前無公開房間',
                    'label_room_id'            => 'Room ID',
                    'lang_code'                => 'zh-TW',
                    'image_size_limit_message' => '請選擇小於5MB的圖片',
                    'image_upload_failed'      => '圖片上傳失敗',
                    'mask_activated'           => ' 已啟動遮蔽模式。',
                    'share_message'            => "Gathron! 聊天已分享 😁\n加入對話吧 🤪\n#gathron #hashchat #chat #ephemeral\n ",
                    'btn_share'                => 'Share',
                    'share_label'              => 'Share',
                ],
            ];
        }

        public static function get(): array
        {
            $lang = self::detectLanguage();
            $all  = self::settings();
            return $all[$lang] ?? $all[DEFAULT_LANG];
        }

        public static function polling(): array
        {
            static $data = null;
            if ($data !== null) {
                return $data;
            }
            $file = __DIR__ . '/polling_config.php';
            $data = is_file($file) ? include $file : [];
            if (!is_array($data)) {
                $data = [];
            }
            return $data;
        }
    }
}

if (!function_exists('cfg')) {
    function cfg(string $key): string
    {
        $settings = Config::get();
        return $settings[$key] ?? '';
    }
}

if (!function_exists('polling_interval')) {
    function polling_interval(string $key, int $default = 1000): int
    {
        $poll = Config::polling();
        return isset($poll[$key]) ? (int)$poll[$key] : $default;
    }
}