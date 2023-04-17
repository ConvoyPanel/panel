<?php

return [
    'power_actions' => [
      'start' => '启动',
      'restart' => '重启',
      'kill' => '强制关闭',
      'shutdown' => '关机',
    ],
    'notices' => [
        'power_action_sent_success' => '成功发送电源操作。可能需要一些时间来处理。',
        'power_action_sent_fail' => '发送电源操作失败。',
    ],

    'state' => '状态',
    'states' => [
        'stopped' => '已停止',
        'running' => '运行中',
        'stopping' => '停止中',
        'starting' => '启动中',
        'shutting_down' => '关机中',
    ],
    'uptime' => '运行时间',
    'poll_status_error' => '获取服务器状态失败。将在5秒后重试...',

    'terminal' => [
        'title' => '终端',
        'description' => '通过网络远程管理您的服务器。',
        'novnc_description' => '兼容性最好，但缺少功能和性能。',
        'xtermjs_description' => '性能最佳，但不适用于所有操作系统。',
    ],

    'server_config' => [
        'title' => '配置此服务器',
        'description' => '您是管理员！您可以点击下方访问此服务器的构建配置并进行编辑。',
        'configure_server' => '配置服务器'
    ],
];
