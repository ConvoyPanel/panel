<?php

return [
    'create_server' => 'Create Server',
    'backup_limit' => '备份限制',
    'bandwidth_limit' => '带宽限制',
    'bandwidth_usage' => 'Bandwidth Usage',
    'snapshot_limit' => 'Snapshots Limit',
    'limit_placeholder' => 'Leave blank for no limit',
    'should_create_vm' => '是否创建虚拟机',
    'start_server_after_installing' => 'Start Server After Completing Installation',
    'vmid_placeholder' => 'Leave blank for random VMID',
    'create_modal' => [
        'title' => '创建服务器',
    ],
    'manage_server' => '管理服务器',
    'server_build' => [
        'title' => '服务器构建',
    ],
    'server_info' => [
        'title' => '服务器信息',
    ],
    'suspension' => [
        'title' => '暂停服务器',
        'description' => '切换服务器是否处于暂停状态。',
        'not_suspended' => '服务器尚未被暂停。',
        'suspended' => '服务器已被暂停。',
    ],
    'delete_server' => [
        'title' => '删除服务器',
        'description' => '此操作将服务器从 Convoy 中删除。备份和其他相关数据将被销毁。通过勾选下面的复选框，你仍可以将虚拟机和数据保存在 Proxmox 节点上。',
        'dont_purge' => '不要彻底删除虚拟机及相关文件',
    ],
];
