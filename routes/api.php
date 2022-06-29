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

  });

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

