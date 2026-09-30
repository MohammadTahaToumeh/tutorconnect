<?php
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\StudyProgramController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TutorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PayPalController;
use App\Http\Controllers\Api\PayPalWebhookController;


Route::post('/paypal/webhook', [PayPalWebhookController::class, 'handle']);

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::get('/register/options', [RegistrationController::class, 'options']);

    Route::post('/register/send-code', [RegistrationController::class, 'sendCode'])
        ->middleware('throttle:5,1');

    Route::post('/register/verify-code', [RegistrationController::class, 'verifyCode'])
        ->middleware('throttle:10,1');
        
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/chat/users/{user}/messages', [ChatController::class, 'messagesWithUser']);
    Route::post('/chat/users/{user}/messages', [ChatController::class, 'sendMessageToUser']);
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/study-programs', [StudyProgramController::class, 'index']);
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/tutors', [TutorController::class, 'index']);
    Route::get('/tutors/{tutorProfile}', [TutorController::class, 'show']);
    Route::get('/tutors/{tutorProfile}/reviews', [ReviewController::class, 'forTutor']);
    Route::get('/profile/options', [ProfileController::class, 'options']);
    Route::post('/profile', [ProfileController::class, 'store']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/chat/me', [ChatController::class, 'me']);
    Route::get('/chat/conversations', [ChatController::class, 'conversations']);
    Route::get('/chat/tutors', [ChatController::class, 'searchTutors']);
    Route::get('/chat/tutors/{tutorProfile}', [ChatController::class, 'showTutor']);
    Route::get('/chat/tutors/{tutorProfile}/messages', [ChatController::class, 'messages']);
    Route::post('/chat/tutors/{tutorProfile}/messages', [ChatController::class, 'sendMessage']);
    Route::get('/chat/tutors/{tutorProfile}/booking-options', [ChatController::class, 'bookingOptions']);
    Route::get('/chat/tutors/{tutorProfile}/slots', [ChatController::class, 'slots']);
    
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/accept', [BookingController::class, 'accept']);
    Route::post('/bookings/{booking}/decline', [BookingController::class, 'decline']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/review', [BookingController::class, 'review']);

    Route::post('/bookings/{booking}/paypal/order', [PayPalController::class, 'createOrder']);
    Route::post('/bookings/{booking}/paypal/capture', [PayPalController::class, 'captureOrder']);

    Route::get('/reviews/me', [ReviewController::class, 'me']);
    Route::get('/reviews/pending', [ReviewController::class, 'pending']);
});
