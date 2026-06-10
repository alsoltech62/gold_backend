<?php
require_once __DIR__ . '/config/config.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');
$parts = explode('/', $uri);

// Simple router
$routes = [
    'api/auth/send-otp'           => 'api/auth/send_otp.php',
    'api/auth/verify-otp'         => 'api/auth/verify_otp.php',
    'api/auth/signup'             => 'api/auth/signup.php',
    'api/user/dashboard'          => 'api/user/dashboard.php',
    'api/user/profile'            => 'api/user/profile.php',
    'api/user/deposit'            => 'api/user/deposit.php',
    'api/user/sip'                => 'api/user/sip.php',
    'api/user/tickets'            => 'api/user/tickets.php',
    'api/user/deliveries'         => 'api/user/deliveries.php',
    'api/silver/rate'             => 'api/silver/rate.php',
    'api/silver/buy'              => 'api/silver/buy.php',
    'api/silver/sell'             => 'api/silver/sell.php',
    'api/gold/rate'               => 'api/gold/rate.php',
    'api/gold/buy'                => 'api/gold/buy.php',
    'api/gold/sell'               => 'api/gold/sell.php',
    'api/transactions'            => 'api/transactions/list.php',
    'api/delivery/request'        => 'api/delivery/request.php',
    'api/admin/dashboard'         => 'api/admin/dashboard.php',
    'api/admin/customers'         => 'api/admin/customers.php',
    'api/admin/transactions'      => 'api/admin/transactions.php',
    'api/admin/gold-rate'         => 'api/admin/gold_rate.php',
    'api/admin/deliveries'        => 'api/admin/deliveries.php',
    'api/admin/tickets'           => 'api/admin/tickets.php',
    'api/user/sip_history'        => 'api/user/sip_history.php',
    'api/user/sip_history.php'    => 'api/user/sip_history.php',
    'api/admin/sip_history'       => 'api/admin/sip_history.php',
    'api/admin/sip_history.php'   => 'api/admin/sip_history.php',
    'api/cron/process_sip'        => 'api/cron/process_sip.php',
    'api/cron/process_sip.php'    => 'api/cron/process_sip.php',
    'api/user/notifications'      => 'api/user/notifications.php',
    'api/user/update-fcm'         => 'api/user/update_fcm.php',
    'api/admin/send-notification' => 'api/admin/send_notification.php',
];

$path = implode('/', array_slice($parts, 0, 3));
if (isset($routes[$path])) {
    require_once __DIR__ . '/' . $routes[$path];
} elseif ($path === 'api/healthz') {
    echo json_encode(['status' => 'ok', 'time' => date('c')]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
}
