<?php

return [
    'ip_allocation' => [
        'empty_state' => '此服务器没有关联的地址。'
    ],
    'display_info' => [
        'title' => '服务器名称',
    ],
    'reinstall' => [
        'title' => '重装系统',
        'description' => '为您的服务器重装系统。',
        'modal' => [
            'title' => '确认重装系统',
            'description' => '您确定要为此服务器重装系统吗？所有数据都将丢失。',
        ]
    ],
    'isos' => [
        'title' => '可挂载的ISO镜像',
        'empty' => '没有ISO镜像',
    ],
    'device_config' => [
        'title' => '设备配置',
        'current' => '当前启动顺序（优先级最高的将首先使用）',
        'unused' => '未使用的设备',
        'unused_empty' => '没有未使用的设备。',
        'no_boot_device_warning' => '尚未配置启动设备。您的虚拟机将无法启动。',
    ],
    'nameservers' => [
        'title' => 'dns服务器',
        'nameserver' => 'dns服务器 :index',
        'add' => '新dns服务器',
    ],
    'auth' => [
        'title' => '身份验证',
    ],
    'hardware' => [
        'bandwidth_used' => '已使用流量',
        'bandwidth_alloted' => '分配的流量',
    ]
];
