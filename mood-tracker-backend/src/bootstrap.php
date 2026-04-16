<?php

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/Support/Helpers.php';
require_once __DIR__ . '/Core/Database.php';
require_once __DIR__ . '/Core/Request.php';
require_once __DIR__ . '/Core/Response.php';
require_once __DIR__ . '/Core/Router.php';
require_once __DIR__ . '/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/Repositories/UserRepository.php';
require_once __DIR__ . '/Repositories/MoodRepository.php';
require_once __DIR__ . '/Repositories/ReminderRepository.php';
require_once __DIR__ . '/Services/AuthService.php';
require_once __DIR__ . '/Services/AnalyzerService.php';
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/MoodController.php';
require_once __DIR__ . '/Controllers/SummaryController.php';
require_once __DIR__ . '/Controllers/AnalyzerController.php';
require_once __DIR__ . '/Controllers/ReferenceController.php';
require_once __DIR__ . '/Controllers/ActivityController.php';
require_once __DIR__ . '/Controllers/ReminderController.php';

session_name($config['session']['name']);
session_start();

$db = Database::connect($config['db']);

$userRepository = new UserRepository($db);
$moodRepository = new MoodRepository($db);
$reminderRepository = new ReminderRepository($db);

$authService = new AuthService($userRepository);
$analyzerService = new AnalyzerService($moodRepository);

$authController = new AuthController($authService, $userRepository);
$moodController = new MoodController($moodRepository);
$summaryController = new SummaryController($moodRepository);
$analyzerController = new AnalyzerController($analyzerService);
$referenceController = new ReferenceController($moodRepository);
$activityController = new ActivityController($moodRepository);
$reminderController = new ReminderController($reminderRepository);

$router = new Router();

$router->post('/api/auth/register', [$authController, 'register']);
$router->post('/api/auth/login', [$authController, 'login']);
$router->post('/api/auth/logout', [$authController, 'logout']);
$router->get('/api/auth/me', [AuthMiddleware::class, 'handle'], [$authController, 'me']);
$router->put('/api/auth/me', [AuthMiddleware::class, 'handle'], [$authController, 'updateMe']);
$router->delete('/api/auth/me', [AuthMiddleware::class, 'handle'], [$authController, 'deleteMe']);

$router->get('/api/reference/options', [$referenceController, 'options']);

$router->post('/api/moods', [AuthMiddleware::class, 'handle'], [$moodController, 'store']);
$router->get('/api/moods', [AuthMiddleware::class, 'handle'], [$moodController, 'index']);
$router->get('/api/moods/{id}', [AuthMiddleware::class, 'handle'], [$moodController, 'show']);
$router->put('/api/moods/{id}', [AuthMiddleware::class, 'handle'], [$moodController, 'update']);
$router->delete('/api/moods/{id}', [AuthMiddleware::class, 'handle'], [$moodController, 'destroy']);

$router->get('/api/summary', [AuthMiddleware::class, 'handle'], [$summaryController, 'summary']);
$router->get('/api/analyzer', [AuthMiddleware::class, 'handle'], [$analyzerController, 'analyze']);

$router->post('/api/activities', [AuthMiddleware::class, 'handle'], [$activityController, 'store']);
$router->get('/api/activities', [AuthMiddleware::class, 'handle'], [$activityController, 'index']);
$router->put('/api/activities/{id}', [AuthMiddleware::class, 'handle'], [$activityController, 'update']);
$router->delete('/api/activities/{id}', [AuthMiddleware::class, 'handle'], [$activityController, 'destroy']);
$router->get('/api/activities/linked-mood', [AuthMiddleware::class, 'handle'], [$activityController, 'linkedToMood']);
$router->get('/api/activities/most-active-day', [AuthMiddleware::class, 'handle'], [$activityController, 'mostActiveDayThisMonth']);

$router->post('/api/reminders', [AuthMiddleware::class, 'handle'], [$reminderController, 'store']);
$router->get('/api/reminders', [AuthMiddleware::class, 'handle'], [$reminderController, 'index']);
$router->get('/api/reminders/due', [AuthMiddleware::class, 'handle'], [$reminderController, 'due']);
$router->put('/api/reminders/{id}', [AuthMiddleware::class, 'handle'], [$reminderController, 'update']);
$router->delete('/api/reminders/{id}', [AuthMiddleware::class, 'handle'], [$reminderController, 'destroy']);

return $router;