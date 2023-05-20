<?php

return [
    'power_actions' => [
        'start' => 'Iniciar',
        'restart' => 'Reiniciar',
        'kill' => 'Terminar',
        'shutdown' => 'Apagar',
    ],
    'notices' => [
        'power_action_sent_success' => 'Acción enviada con éxito. Puede tardar un momento en procesarse.',
        'power_action_sent_fail' => 'No se ha podido enviar la acción de energia al servidor.',
    ],

    'state' => 'Estado',
    'states' => [
        'stopped' => 'Detenido',
        'running' => 'Ejecutando',
        'stopping' => 'Deteniendo',
        'starting' => 'Iniciando',
        'shutting_down' => 'Apagando',
    ],
    'uptime' => 'Tiempo de Actividad',
    'poll_status_error' => 'Fallo al sondear el estado del servidor. Reintentando en 5 segundos...',

    'terminal' => [
        'title' => 'Consola',
        'description' => 'Gestiona remotamente tu servidor desde la web.',
        'novnc_description' => 'El mejor por compatibilidad, pero carece de prestaciones y rendimiento.',
        'xtermjs_description' => 'Lo mejor para el rendimiento, pero no funciona para todos los sistemas operativos.',
    ],

    'server_config' => [
        'title' => 'Configurar este Servidor',
        'description' => '¡Usted es un administrador! Puede hacer clic a continuación para visitar la configuración de construcción de este servidor y realizar modificaciones.',
        'configure_server' => 'Configurar Servidor',
    ],
];
