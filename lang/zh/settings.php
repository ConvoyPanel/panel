<?php

return [
    'ip_allocation' => [
        'empty_state' => '目前没有 IP 地址与该服务器关联。'
    ],
    'display_info' => [
        'title' => '服务器名称',
    ],
    'reinstall' => [
        'title' => '重装服务器',
        'description' => '将服务器恢复到初始状态。',
        'modal' => [
            'title' => '确认重装',
            'description' => '你确定要重装服务器吗？所有未备份数据将会丢失。',
        ]
    ],
    'isos' => [
        'title' => '可使用的 ISO 镜像',
        'empty' => '没有可供选择的 ISO 镜像',
    ],
    'device_config' => [
        'title' => '设备配置',
        'current' => '当前的 Boot 顺序（越排前边优先级越高）',
        'unused' => '未使用的设备',
        'unused_empty' => '当前没有未使用的设备。',
        'no_boot_device_warning' => '尚未配置 Boot 策略。你的虚拟机会无法启动。',
    ],
    'nameservers' => [
        'title' => '域名解析服务器',
        'nameserver' => '域名解析服务器 :index',
        'add' => '新建域名服务器',
    ],
    'auth' => [
        'title' => '身份验证',
    ],
    'hardware' => [
        'bandwidth_used' => '已使用带宽',
        'bandwidth_alloted' => '所分配带宽',
    ]
];