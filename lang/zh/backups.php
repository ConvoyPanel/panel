<?php

return [
    'new_backup' => '新建备份',
    'empty_state' => '没有备份',
    'counter_tooltip' => '您已创建 :count 个，最大可创建 :max 个备份',
    'create_modal' => [
        'title' => '创建备份',
        'description' => '创建备份将复制您的服务器文件。根据服务器的大小，这可能需要一些时间。',
        'compression_type' => '压缩类型',
        'mode' => '模式',
        'modes' => [
            'snapshot' => '快照',
            'suspend' => '暂停',
            'kill' => '强制停止'
        ]
    ],
    'delete_modal' => [
        'title' => '删除 :name',
        'description' => '您确定要删除此备份吗？',
    ],
    'restore_modal' => [
        'title' => '从 :name 还原',
        'description' => '您确定要从此备份还原吗？',
    ],
    'notices' => [
        'backup_deleted' => '已删除 :name',
        'backup_restored' => '开始从 :name 还原服务器',
    ]
];