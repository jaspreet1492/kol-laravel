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
use App\Http\Controllers\API\DealController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\BookmarkController;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\BannerController;
use App\Http\Controllers\API\InformativeVideoController;
use App\Http\Controllers\API\DashboardController;


Route::middleware(['api'])->group(function () {

    Route::post('register',[AuthController::class,'registration']);
    Route::post('verify-OTP',[AuthController::class,'verifyOTP']);
    Route::post('resend-OTP',[AuthController::class,'resendOTP']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('login-with-google', [AuthController::class, 'loginWithGoogle']);
    Route::put('update-role', [AuthController::class, 'updateRole']);
    Route::patch('check-email-forgot-password',[AuthController::class,'checkEmailForgotPassword']);
    Route::put('forgot-password',[AuthController::class,'forgotPassword']);
    Route::get('varifyResetpassword',[ResetPasswordController::class,'varifyResetpassword'])->name('varifyResetpassword');
    Route::post('changePassword',[ResetPasswordController::class,'changePassword'])->name('changePassword');
    Route::post('resend-verification-email', [AuthController::class, 'sendVerificationEmail']);
    Route::post('logout', [AuthController::class, 'logout']);


    Route::get('get/roles', [AuthController::class, 'getRoles']);
    
  });

  Route::group(['middleware' => ['jwt.verify']], function() {
    
    Route::put('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('user/add-user-address', [UserController::class, 'addUserAddress']);
    Route::post('store-user-image',[UserController::class,'storeUserImage']);
    Route::post('kol-profile/add-update',[KolProfileController::class,'AddOrUpdateKolProfile']);
    Route::get('kol-profile/view',[KolProfileController::class,'getKolProfileById']);
    Route::get('kol-profile/view-details',[KolProfileController::class,'getKolProfile']);
    Route::get('kol-profile/list',[KolProfileController::class,'getProfileList']);
    Route::put('kol-profile/add-view-count',[KolProfileController::class,'saveProfileView']);
    Route::post('announcement/add-update',[AnnouncementController::class,'AddorUpdateAnnouncement']);
    Route::post('deal/add-update',[DealController::class,'AddorUpdateDeal']);
    Route::post('deal/request-deal',[DealController::class,'requestDeal']);
    Route::post('deal/watch-deal',[DealController::class,'watchDeal']);
    Route::delete('deal/delete',[DealController::class,'deleteDeal']);
    Route::get('deal/my-deals',[DealController::class,'getDealsListByLoggedInKolUser']);
    Route::post('order/place-order',[OrderController::class,'placeOrder']);
    Route::get('order/get_order_summary',[OrderController::class,'getOrderSummary']);
    Route::get('order/get_user_order_history',[OrderController::class,'getUserOrderHistory']);
    Route::get('order/get_kol_order_history',[OrderController::class,'getKolOrderHistory']);
    Route::post('bookmark/add',[BookmarkController::class,'AddBookmark']);
    Route::post('feedback/add',[FeedbackController::class,'AddFeedback']);
    Route::delete('bookmark/delete',[BookmarkController::class,'deleteBookmark']);
    Route::get('bookmark/list',[BookmarkController::class,'getBookmarks']);
    Route::get('feedback/end-user-list',[FeedbackController::class,'getEndUserFeedbackList']);
    Route::get('feedback/kol-user-list',[FeedbackController::class,'getKolFeedbackList']);
    Route::get('kol-type/list',[KolTypeController::class,'getKolTypeList']);
    Route::get('announcement/view',[AnnouncementController::class,'getAnnouncementById']);
    Route::get('announcement/kol-user-id-list',[AnnouncementController::class,'getAnnouncementListByKolUserId']);
    Route::get('announcement/list',[AnnouncementController::class,'getAnnouncementList']);
    Route::get('announcement/all-list',[AnnouncementController::class,'getAllAnnouncementList']);
    Route::delete('announcement/delete',[AnnouncementController::class,'deleteAnnouncement']);
    Route::post('announcement/active-inactive-status',[AnnouncementController::class,'AnnouncementActiveInactive']);
    Route::post('Chat/send-message',[ChatController::class,'sendMessage']);
    Route::get('Chat/chat-list-users',[ChatController::class,'getChatDataUsers']);
    Route::get('Chat/chat-list',[ChatController::class,'getChatData']); 
    Route::get('Chat/delete-msg',[ChatController::class,'deleteChat']);
    Route::put('Chat/edit-msg',[ChatController::class,'editChat']);
    Route::get('view-all-user',[UserController::class,'displayAllUser']);
    Route::get('view-user-details', [UserController::class, 'getUserDetailsByID']);
    Route::post('update-user-data', [UserController::class, 'updateUserDetails']);
    // Route::post('add-user-address', [UserController::class, 'storeUserAddress']);
     
  });
  
  
  Route::group(['middleware' => 'isAdmin'], function () {
  
    Route::post('dashboard/add-update-banner',[DashboardController::class,'AddUpdateBanner']);
    Route::post('dashboard/add-update-faq',[DashboardController::class,'AddUpdateFaq']);
    Route::delete('dashboard/banner-delete',[DashboardController::class,'deleteBanner']);
    Route::post('dashboard/add-update-information',[DashboardController::class,'AddUpdateInformativeVideo']);
    Route::delete('dashboard/delete-information',[DashboardController::class,'deleteInformativeVideo']);
    Route::delete('dashboard/delete-faq',[DashboardController::class,'deleteFaq']);
    Route::put('kol-profile/is_featured',[KolProfileController::class,'FeatureKolProfile']);
    Route::post('kol-type/add-update',[KolTypeController::class,'AddorUpdateKolType']);
    Route::post('kol-type/active-inactive',[KolTypeController::class,'ActiveInactiveKolType']);
  }); 

  Route::get('language-list',[KolTypeController::class,'getLanguage']);
  Route::get('state-list',[KolTypeController::class,'getState']);
  Route::get('stream-list',[KolTypeController::class,'getStream']);
  Route::get('dashboard/banner-list',[DashboardController::class,'getBannerList']);
  Route::get('dashboard/information-list',[DashboardController::class,'getInformativeVideoList']);
  Route::get('dashboard/faq-list',[DashboardController::class,'getFaqList']);
  Route::get('dashboard/get-total-count',[DashboardController::class,'getTotalCount']);
  Route::post('dashboard/contactUs',[DashboardController::class,'contactUs']);
  Route::get('kol-profile/featured-list',[KolProfileController::class,'getFeaturedProfileList']);
  Route::get('deal/view-by-id',[DealController::class,'getDealsById']);
  Route::get('deal/list-deals',[DealController::class,'getDealsListByKolProfileId']);
  Route::get('deal/list-kol-deals',[DealController::class,'getDealsListByKolUserId']);

 

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

