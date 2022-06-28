<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use App\Models\UserAddress;
use Validator;
use Mail;
use JWTAuth;
use App\Models\UserTokens;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Services\UserService;

class UserController extends Controller
{
  private $userService;
  public function __construct(UserService $userService)
  {
    $this->userService = $userService;
  }
  public function displayAllUser()
  {
    $checkAllUserData = $this->userService->getAllUser();
    $status_code = 200;
    $msg = __('api_string.all_users');
    $successMsg = __('api_string.success_message');
    return response()->json(['status' => $status_code, 'success' => $successMsg, 'message' => $msg, 'data' => $checkAllUserData]);
  }

  public function getUserDetailsByID(Request $request)
  {

    $user = Auth::user();
    // dd($user);

    // return response()->json([ 'statusCode'=> 200, 'success'=> true,'message' => ' currently logged in user', 'user'=>$user]);
  }

  public function storeUserAddress(Request $request)
  {
    $userId = Auth::user()->id;
    $createUserAddress = $this->userService->addUserAdress($userId, $request);
  }


  public function updateUserDetails(Request $request)
  {
    $response = $this->userService->updateData($request);
    return response()->json(['statusCode' => 201, 'success' => true, 'message' => "data updated successfully",]);
  }

  public function addUserAddress(Request $request)
  {
    $response = $this->userService->addAdress($request);
    return response()->json(['statusCode' => 201, 'success' => true, 'message' => "address added successfully",]);
    // }
  }
}
