<?php

return [
    'create_backup' => 'Create Backup',
    'empty_state' => '目前还没有任何可用备份。',
    'counter_tooltip' => '你已创建了 :count 个备份，超过了 :max 个的限制。',
    'create_modal' => [
        'title' => '创建备份',
        'description' => '创建备份将拷贝你的服务器文件。这可能需要一段时间，具体取决于服务器体量。',
        'compression_type' => '压缩类型',
        'mode' => '备份模式',
        'modes' => [
            'snapshot' => '快照',
            'suspend' => '暂停',
            'kill' => '终结',
        ],
    ],
    'delete_modal' => [
        'title' => '删除备份 :name',
        'description' => '你确定要删除该备份吗？',
    ],
    'restore_modal' => [
        'title' => '从备份 :name 中复原',
        'description' => '你确定要以该备份为样板复原吗？',
    ],
    'notices' => [
        'backup_deleted' => '已成功删除备份 :name',
        'backup_restored' => '正在使用备份 :name 来复原服务器',
    ],
];
