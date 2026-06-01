<?php

return [
    // Rutas donde se aplican los encabezados CORS.
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    // Métodos HTTP permitidos para solicitudes cross-origin. '*' permite todos.
    'allowed_methods' => ['*'],

    // Orígenes permitidos. En producción conviene definir FRONTEND_URL.
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost',
        'http://localhost:3000',
    ],

    // Patrones regex para permitir orígenes dinámicos (vacío = no se usan patrones).
    'allowed_origins_patterns' => [],

    // Encabezados permitidos en la solicitud del cliente. '*' permite todos.
    'allowed_headers' => ['*'],

    // Encabezados de respuesta expuestos al navegador (además de los simples).
    'exposed_headers' => [],

    // Tiempo (en segundos) para cachear la preflight request. 0 = sin caché.
    'max_age' => 0,

    // true permite cookies/credenciales en requests CORS.
    // Requiere orígenes explícitos (no comodín '*').
    'supports_credentials' => true,
];
