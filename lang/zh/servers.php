<?php

return [
    'new_server' => '新建服务器',
    'backup_limit' => '备份限制',
    'bandwidth_limit' => '带宽限制',
    'snapshots_limit' => '快照限制',
    'should_create_vm' => '是否创建虚拟机',
    'create_modal' => [
        'title' => '创建服务器',
    ],
    'manage_server' => '管理服务器',
    'server_build' => [
        'title' => '服务器构建',
    ],
    'server_info' => [
        'title' => '服务器信息'
    ],
    'suspension' => [
        'title' => '暂停',
        'description' => '切换服务器的暂停状态。',
        'not_suspended' => '该服务器未暂停',
        'suspended' => '该服务器已暂停',
    ],
    'delete_server' => [
        'title' => '删除服务器',
        'description' => '服务器将从Convoy中删除。备份和其他相关数据将被销毁。但是，您可以勾选下面的复选框以保留Proxmox节点上的虚拟机和数据。',
        'dont_purge' => '不清除虚拟机和相关文件'
    ]
];
