<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\API\KolProfileController;
use App\Http\Controllers\API\KolTypeController;
use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\BookmarkController;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\BannerController;


Route::middleware(['api'])->group(function () {

    Route::post('register',[AuthController::Class,'registration']);
    Route::post('verify-OTP',[AuthController::Class,'verifyOTP']);
    Route::post('resend-OTP',[AuthController::Class,'resendOTP']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('login-with-google', [AuthController::class, 'loginWithGoogle']);
    Route::put('update-role', [AuthController::class, 'updateRole']);
    //reset password if user forgot his password
    Route::patch('check-email-forgot-password',[AuthController::Class,'checkEmailForgotPassword']);
    Route::put('forgot-password',[AuthController::Class,'forgotPassword']);
    Route::get('varifyResetpassword',[ResetPasswordController::Class,'varifyResetpassword'])->name('varifyResetpassword');
    Route::post('changePassword',[ResetPasswordController::Class,'changePassword'])->name('changePassword');
    //ask for otp if first time otp not get 
    Route::post('resend-verification-email', [AuthController::class, 'sendVerificationEmail']);
    Route::post('logout', [AuthController::class, 'logout']);


    Route::get('get/roles', [AuthController::class, 'getRoles']);
    
  });

  Route::group(['middleware' => ['jwt.verify']], function() {
    
    Route::put('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('kol-profile/add-update',[KolProfileController::Class,'AddOrUpdateKolProfile']);
    Route::get('kol-profile/view',[KolProfileController::Class,'getKolProfileById']);
    Route::get('kol-profile/list',[KolProfileController::Class,'getProfileList']);
    Route::get('kol-profile/featured-list',[KolProfileController::Class,'getFeaturedProfileList']);
    Route::put('kol-profile/add-view-count',[KolProfileController::Class,'saveProfileView']);
    Route::put('kol-profile/is_featured',[KolProfileController::Class,'FeatureKolProfile']);
    Route::post('announcement/add-update',[AnnouncementController::Class,'AddorUpdateAnnouncement']);
    Route::post('bookmark/add',[BookmarkController::Class,'AddBookmark']);
    Route::post('feedback/add',[FeedbackController::Class,'AddFeedback']);
    Route::delete('bookmark/delete',[BookmarkController::Class,'deleteBookmark']);
    Route::get('bookmark/list',[BookmarkController::Class,'getBookmarks']);
    Route::get('feedback/end-user-list',[FeedbackController::Class,'getEndUserFeedbackList']);
    Route::get('feedback/kol-user-list',[FeedbackController::Class,'getKolFeedbackList']);
    Route::post('kol-type/add-update',[KolTypeController::Class,'AddorUpdateKolType']);
    Route::get('kol-type/list',[KolTypeController::Class,'getKolTypeList']);
    Route::post('kol-type/active-inactive',[KolTypeController::Class,'ActiveInactiveKolType']);
    Route::get('announcement/view',[AnnouncementController::Class,'getAnnouncementById']);
    Route::get('announcement/list',[AnnouncementController::Class,'getAnnouncementList']);
    Route::get('announcement/all-list',[AnnouncementController::Class,'getAllAnnouncementList']);
    Route::delete('announcement/delete',[AnnouncementController::Class,'deleteAnnouncement']);
    Route::post('announcement/active-inactive-status',[AnnouncementController::Class,'AnnouncementActiveInactive']);
    Route::post('Chat/send-message',[ChatController::Class,'sendMessage']);
    Route::get('Chat/chat-list-users',[ChatController::Class,'getChatDataUsers']);
    Route::get('Chat/chat-list',[ChatController::Class,'getChatData']);
    Route::get('Chat/delete-msg',[ChatController::Class,'deleteChat']);
    Route::put('Chat/edit-msg',[ChatController::Class,'editChat']);
    
  });
  
  
  Route::group(['middleware' => 'isAdmin'], function () {

    Route::get('view-all-user',[UserController::Class,'displayAllUser']);
    Route::get('view-user-details', [UserController::class, 'getUserDetailsByID']);
    Route::post('update-user-data', [UserController::class, 'updateUserDetails']);
    Route::get('get-user-address', [UserController::class, 'addUserAddress']);
    Route::post('add-user-address', [UserController::class, 'storeUserAddress']);
    Route::get('category/list',[CategoryController::Class,'getCategory']);
    Route::post('category/store',[CategoryController::Class,'store']);
    Route::get('category/view',[CategoryController::Class,'viewCategory']);
    Route::post('category/edit',[CategoryController::Class,'editCategory']);
    Route::put('category/edit/status',[CategoryController::Class,'ChangeCategoryStatus']);
    Route::post('updateCategory',[CategoryController::Class,'makeUpdation']);
    Route::post('banner/add-banner',[BannerController::Class,'AddBanner']);
    Route::get('banner/list',[BannerController::Class,'getBannerList']);
    Route::delete('banner/delete',[BannerController::Class,'deleteBanner']);

  }); 

  Route::get('language-list',[KolTypeController::Class,'getLanguage']);
  Route::get('state-list',[KolTypeController::Class,'getState']);
  Route::get('stream-list',[KolTypeController::Class,'getStream']);


 

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

