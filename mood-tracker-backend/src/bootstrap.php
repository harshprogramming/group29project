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
require_once __DIR__ . '/Services/AuthService.php';
require_once __DIR__ . '/Services/AnalyzerService.php';
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/MoodController.php';
require_once __DIR__ . '/Controllers/SummaryController.php';
require_once __DIR__ . '/Controllers/AnalyzerController.php';
require_once __DIR__ . '/Controllers/ReferenceController.php';

session_name($config['session']['name']);
session_start();

$db = Database::connect($config['db']);

$userRepository = new UserRepository($db);
$moodRepository = new MoodRepository($db);

$authService = new AuthService($userRepository);
$analyzerService = new AnalyzerService($moodRepository);

$authController = new AuthController($authService, $userRepository);
$moodController = new MoodController($moodRepository);
$summaryController = new SummaryController($moodRepository);
$analyzerController = new AnalyzerController($analyzerService);
$referenceController = new ReferenceController($moodRepository);

$router = new Router();

$router->post('/api/auth/register', [$authController, 'register']);
$router->post('/api/auth/login', [$authController, 'login']);
$router->post('/api/auth/logout', [$authController, 'logout']);
$router->get('/api/auth/me', [$authController, 'me']);

$router->get('/api/reference/options', [$referenceController, 'options']);

$router->post('/api/moods', [AuthMiddleware::class, 'handle'], [$moodController, 'store']);
$router->get('/api/moods', [AuthMiddleware::class, 'handle'], [$moodController, 'index']);
$router->get('/api/moods/{id}', [AuthMiddleware::class, 'handle'], [$moodController, 'show']);
$router->put('/api/moods/{id}', [AuthMiddleware::class, 'handle'], [$moodController, 'update']);
$router->delete('/api/moods/{id}', [AuthMiddleware::class, 'handle'], [$moodController, 'destroy']);

$router->get('/api/summary', [AuthMiddleware::class, 'handle'], [$summaryController, 'summary']);
$router->get('/api/analyzer', [AuthMiddleware::class, 'handle'], [$analyzerController, 'analyze']);

return $router;