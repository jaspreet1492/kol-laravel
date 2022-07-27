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

    $userId = Auth::user()->id;
    $user = $this->userService->getUserById($userId);
    
    return response()->json([ 'statusCode'=> 200, 'success'=> true,'message' => ' currently logged in user', 'user'=>$user]);
  }

  public function storeUserAddress(Request $request)
  {
    $userId = Auth::user()->id;
    $createUserAddress = $this->userService->addUserAdress($userId, $request);
  }

  public function storeUserImage(Request $request)
  {
    $userId = Auth::user()->id;
    $uploadUserImage = $this->userService->storeUserImage($request, $userId);

    return response()->json(['statusCode' => 201, 'success' => true, 'message' => "Image uploaded successfully"]);
  }


  public function updateUserDetails(Request $request)
  {
    $response = $this->userService->updateData($request);
    return response()->json(['statusCode' => 201, 'success' => true, 'message' => "data updated successfully",]);
  }

  public function addUserAddress(Request $request)
  {
    $userId = Auth::user()->id;
    $roleId = Auth::user()->role_id;

    if($roleId == 2 || $roleId == 3){  
      $valdiation = Validator::make($request->all(),[
        'phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|max:10' 
      ]);

      if($valdiation->fails()) {
          $msg = $valdiation->errors()->first();
          return response()->json(["message"=>$msg, "statusCode"=>422]);
      }  

      if($request['state']){
        $states = Config('app.states');
        $stateCheck = in_array($request['state'],$states);
  
        if(!$stateCheck){
          $msg=__("api_string.valid_state");
          return response()->json(["status"=>false,'statusCode'=>301,"message"=>$msg]);
        }
      }

      $updateUserDetails = $this->userService->UpdateUserDetails($request,$userId);

      $checkAddress = $this->userService->checkAddressExistOrNot($userId);

      if($checkAddress){
        //update address
        $checkProfile = $this->userService->UpdateAddress($request,$userId);
        $msg=__("api_string.address_added");
        return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);

      }else{
        //add address
        $addAddress = $this->userService->AddAddress($request,$userId);
        $msg=__("api_string.address_added");
        return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);     
      }
      return response()->json(['statusCode' => 201, 'success' => true, 'message' => "address added successfully",]);
    }
  }
}
