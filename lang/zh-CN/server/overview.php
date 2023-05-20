<?php

return [
    'power_actions' => [
        'start' => '开机',
        'restart' => '重启',
        'kill' => '终结',
        'shutdown' => '关机',
    ],
    'notices' => [
        'power_action_sent_success' => '已成功发送电源操作。可能需要一段时间进行处理。',
        'power_action_sent_fail' => '发送电源操作失败。',
    ],

    'state' => '状态',
    'states' => [
        'stopped' => '已关机',
        'running' => '运行中',
        'stopping' => '关机中',
        'starting' => '开机中',
        'shutting_down' => '正在关机',
    ],
    'uptime' => '在线时长',
    'poll_status_error' => '获取服务器状态失败。将在 5 秒后再次尝试......',

    'terminal' => [
        'title' => '终端',
        'description' => '从网页远程管理服务器',
        'novnc_description' => '兼容性佳，但性能不好且缺乏功能',
        'xtermjs_description' => '性能极佳，但无法保证能在每个操作系统上运作。',
    ],

    'server_config' => [
        'title' => '配置该服务器',
        'description' => '你拥有管理员权限，你可以访问下边的服务器构建配置并作出编辑。',
        'configure_server' => '配置服务器',
    ],
];
