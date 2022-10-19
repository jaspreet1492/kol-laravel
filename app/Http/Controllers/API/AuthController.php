<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Auth;
use Session;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserAddress;
use Validator;
use Mail;
use Config;
use JWTAuth;
use App\Models\UserTokens;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Services\UserService;

class AuthController extends Controller
{
    
    
    public $token = true;
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }
    
    /**
     * make registration api
     * call user service function to save record in database and send mail to user for verification the valid mail
     * @param request
     * @return json format
     * @author Vikram
     */
    

//      public function getRoles(Request $request)
//      {
//         $role_id=$request['role_id'];
//         $roles=Config::get('app.roles');
//         if (in_array("Joe", $roles))
//   {
//       echo "Match found";
//   } else  {
//       echo "Match not found";
//   }
//      }
    public function registration(Request $request) {
        try {
            $valdiation = Validator::make($request->all(),[
                 'name' => 'required',
                 'email' => 'required|email',
                 'password' => 'required_without:firebase_token',  
                 'role_id' => 'required|integer',
                 'firebase_token'=>'required_without:password'
             ]);
             if($valdiation->fails()) {
                 $msg = __("api_string.invalid_fields");
                 return response()->json(["message"=>$msg, "statusCode"=>422]);
             }
            $roles=Config::get('app.roles');
            unset($roles[0]);
            if(in_array($request['role_id'],$roles)){
                if($request['firebase_token']){
                    $checkEmail = $this->userService->checkEmail($request['email']);
                    if($checkEmail){
                            $token = $this->userService->generateJwtToken($checkEmail);
                            if($token){
                                $response = [];
                                $response['token'] = $token;
                                $response['user_name'] = $checkEmail['name'];
                                $response['email'] = $checkEmail['email'];
                                $response['role_id'] = $checkEmail['role_id'];
                                $msg =  __("api_string.user_register_by_firebase");
                                return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg,"data"=>$response]);     
                            }else{
                                $msg = __("api_string.error");
                                return response()->json(["status"=>false, "statusCode"=>500,"message"=>$msg]);                
                            }
                    }else{
                        //create users
                        $saveResponse = $this->userService->createUser($request,$otp=null,$request['role_id']);
                        if($saveResponse){
                            $response = [];
                            $response['token'] = $saveResponse;
                            $response['user_name'] = $request['name'];
                            $response['email'] = $request['email'];
                            $response['role_id'] = $request['role_id'];
                            $msg =  __("api_string.user_register_by_firebase");
                            return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg,"data"=>$response]); 
                        }
                    }
                }else{
                    $otp = rand(100000,999999);
                    // check email
                    $checkEmail = $this->userService->checkEmail($request['email']);
                    if($checkEmail){
                            // move to login
                        $msg=__("api_string.user_exist");
                        return response()->json(["status"=>false,'statusCode'=>301,"message"=>$msg]);
                    }else{
                        //create users
                        $saveResponse = $this->userService->createUser($request,$otp,$request['role_id']);
                        if($saveResponse){
                            $msg =  __("api_string.verify_email");
                            return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg,'otp'=>$otp,"role_id"=>$request['role_id'],"email"=>$request['email'],]); 
                        }
                    }
                }
            } else{
                return response()->json(["message"=>"Select a valid role", "statusCode"=>422]);
            }
    
        } catch (\Throwable $th) {
            $msg = __("api_string.error");
            return response()->json(["message"=>$th->getMessage(), "statusCode"=>500]);
        }
     }
       
     /**
      * @param request otp
      * by this function verify the otp and send the mail
      * @return token with User detail
      * @author sharanveer kannaujiya
      */

    
      public function verifyOTP(Request $request){
        try {
            $valdiation = Validator::make($request->all(),
            [
                'otp'=> 'required|integer|min:6',
                'email'=> 'required|email'
            ]);
            if($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>422]);
            }
            $otp = $request['otp'];
            $checkValidOtp = $this->userService->checkOtp($request);
            if($checkValidOtp){ 
                if($checkValidOtp['is_varified'] != 1){
                    $makeUserVerify = $this->userService->makeUserVerifiy($otp);
                    if($makeUserVerify){
                        $verifyToken = $this->userService->verifyEmailOtp($otp,$checkValidOtp['id']);
                         $response = [];
                        $response['token'] = $verifyToken['token'];
                        $response['user_name'] = $checkValidOtp['name'];
                        $response['email'] = $checkValidOtp['email'];
                        $response['role_id'] = $checkValidOtp['role_id'];
                        $response['otp'] = $checkValidOtp['otp'];
                        $response['status'] = $checkValidOtp['status'];
                         if($verifyToken){
                            $msg =  __("api_string.successfully_verfied");
                            return response()->json(["statusCode"=>200, "status"=>true, "message"=>$msg, "data"=>$response]);
                         }
                        }else{
                             $msg =  __("api_string.error");
                        return response()->json(["statusCode"=>500,"status"=>false,"message"=>$msg]);
                    }
                }else{
                    $msg= __("api_string.email_already_varified");
                    return response()->json(["statusCode"=>401,"status"=>true,"message"=>$msg]);
                }
            }else{
                $msg= __("api_string.incorrect_otp");
                return response()->json(["statusCode"=>401,"status"=>true,"message"=>$msg]);
                //  incorrect otp you are enter..
            }

        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }    
    }
    
    

    public function loginWithGoogle(Request $request){
       try {
            $valdiation = Validator::make($request->all(),[
                'name' => 'required',
                'email' => 'required|email',
                'firebase_token'=>'required'
            ]);
            if($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>422]);
            }
            $checkEmail = $this->userService->checkEmail($request['email']);
            if($checkEmail){
                if($checkEmail['role_id'] !==0){
                    $token = $this->userService->generateJwtToken($checkEmail);
                    if($token){
                        $response = [];
                        $response['token'] = $token;
                        $response['logged_in_user'] = $checkEmail['id'];
                        $response['user_name'] = $checkEmail['name'];
                        $response['email'] = $checkEmail['email'];
                        $response['role_id'] = $checkEmail['role_id'];
                        $msg =  __("api_string.login_success");
                        return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg,"data"=>$response]);     
                    }else{
                        $msg = __("api_string.error");
                        return response()->json(["status"=>false, "statusCode"=>500,"message"=>$msg]);                
                    }
                }else{
                    $response['user_name'] = $request['name'];
                    $response['email'] = $request['email'];
                    $response['role_id'] = 0;
                    $msg =  __("api_string.assign_role_id");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg,"data"=>$response]); 
                }
            }else{
                //create users
                $roleId = 0;
                if($request['role_id']){
                    $roleId = $request['role_id'];
                }
                $saveResponse = $this->userService->createUser($request,$otp=null,$roleId);
                if($saveResponse){
                    $response = [];
                    // $response['token'] = $saveResponse;
                    $response['user_name'] = $request['name'];
                    $response['email'] = $request['email'];
                    $response['role_id'] = $roleId;
                    $msg =  __("api_string.assign_role_id");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg,"data"=>$response]); 
                }
            }
       } catch (\Throwable $th) {
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
       }
    }

    public function updateRole(Request $request){
        try {
            $valdiation = Validator::make($request->all(),[
                'email' => 'required|email',
                'role_id'=>'required',

            ]);
            if($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>422]);
            }
            $checkEmail = $this->userService->checkEmail($request['email']);
            if($checkEmail && $checkEmail['role_id']== 0){
                $roles=Config::get('app.roles');
                unset($roles[0]);
                if($request['role_id'] && in_array($request['role_id'],$roles)){
                    $roleId = $request['role_id'];
                $updateResponse = $this->userService->updateRoleByUserEmail($request);
                }else{
                    return response()->json(["status"=>false,'statusCode'=>422,"data"=>"Select valid role"]);
                }
                if($updateResponse){
                    $token = $this->userService->generateJwtToken($checkEmail);
                            if($token){
                                $response = [];
                                $response['token'] = $token;
                                $response['user_name'] = $checkEmail['name'];
                                $response['email'] = $checkEmail['email'];
                                $response['role_id'] = $request['role_id'];
                                $msg =  __("api_string.user_role_token");
                                return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg,"data"=>$response]);
                            }else{
                                $msg = __("api_string.role_updated_status");
                                return response()->json(['statusCode'=>204,'Status'=>false,'msg'=>$msg]);
                            }        
                }
            }

        else{
            $msg = __("api_string.role_assigned");
            return response()->json(['statusCode'=>500,'Status'=>false,'msg'=>$msg]);
        }
    } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }
    public function login(Request $request){ 
        try {
            $valdiation = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);
            if($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>401]);
            }
            $otp = rand(100000,999999);
            // check email 
            $checkEmail = $this->userService->checkEmail($request['email']);
            if($checkEmail){
                //check password 
                $checkPass = $this->userService->userLogin($request,$checkEmail['password'],$checkEmail['id']);
                if($checkPass){
                    //check verified
                    if($checkEmail['is_varified'] == 1){
                        $response = [];
                        $response['token'] = $checkPass;
                        $response['logged_in_user'] = $checkEmail['id'];
                        $response['user_name'] = $checkEmail['name'];
                        $response['email'] = $checkEmail['email'];
                        $response['role_id'] = $checkEmail['role_id'];
                        $msg= __("api_string.login");
                        return response()->json(["statusCode"=>200,"status"=>true,"message"=>$msg, "data"=>$response]);
                    }else{
                        // not verified
                        $mailResponse = $this->userService->sendVerificationCode($checkEmail['id'],$checkEmail['email_verification_code'], $request,$otp);
                        $msg = __("api_string.email_not_varified");
                        return response()->json(["status"=>true,'statusCode'=>401,"message"=>$msg,'otp'=>$otp,'email'=>$request['email']]);
                    }
                }else{
                       // incorrect password
                    $msg= __("api_string.incorrect_password");
                    return response()->json(["statusCode"=>403,"status"=>false,"message"=>$msg]);
                }
            }else{
                // incorrect email id
                $msg= __("api_string.email_exist");
                return response()->json(["statusCode"=>404,"status"=>false,"message"=>$msg]);
            }
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    } 

    public function resendOTP(Request $request){
        try {
            $valdiation = Validator::make($request->all(),[
                'email' => 'required|email',
            ]);
            if($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>422]);
            }
            $otp = rand(100000,999999);
            //  check email
            $checkEmail = $this->userService->checkEmail($request['email']);
            if($checkEmail){
                if($checkEmail['is_varified']==1){
                    $msg = __("api_string.email_already_varified");
                    return response()->json(["message"=>$msg, "statusCode"=>401]);
                   
                }
                else{
                    $updateUserOtp = $this->userService->insertNewOtp($otp,$checkEmail['id'],$checkEmail['otp'],$request['email']);
                    if($updateUserOtp){
                        $msg= __("api_string.resent_otp");
                        return response()->json(["statusCode"=>200,"status"=>true,"message"=>$msg,'otp'=>$otp]);
                    }else{
                        $msg= __("api_string.error");
                        return response()->json(["statusCode"=>500,"status"=>false,"message"=>$msg]);
                    }
                }
                // generate otp

            }else{
                // Invalid email 
                $msg= __("api_string.email_exist");
                return response()->json(["statusCode"=>401,"status"=>false,"message"=>$msg]);
            }
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
           
    }
    /**
     * @param request
     * validate data,check mail id, check password,
     * @return token with User details
     * @author sharanveer kannaujiya
     */
    
    public function resetPassword(Request $request){

        try {
            $valdiation = Validator::make($request->all(),[
                'old_password' => 'required',
                'new_password' =>'required',
            ]);

            if($valdiation->fails()) {

                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>422]);
            }
            $userId = auth()->user()->id;
            // check email and password 
            $checkEmail = $this->userService->getUserById($userId);
            if($checkEmail){
                 // check password
                 $checkPass = $this->userService->checkPassword($request['old_password']);
                 if($checkPass){
                     $token = $this->userService->updatePassword($request,$userId,$checkEmail['email']);
                     if($token){
                         //updated 
                         $response = [];
                         $response['token'] = $token;
                         $response['user_details'] = $checkEmail;
                         $msg= __("api_string.reset_password");
                         return response()->json(["statusCode"=>201,"status"=>true,"message"=>$msg,"data"=>$response]);
                     }else{
                        $msg= __("api_string.error");
                        return response()->json(["statusCode"=>403,"status"=>false,"message"=>$msg]);
                     }
                 }else{
                    $msg= __("api_string.incorrect_password");
                    return response()->json(["statusCode"=>403,"status"=>false,"message"=>$msg]);
                     // incorrect password
                 }
            }else{
                $msg= __("api_string.error");
                return response()->json(["statusCode"=>403,"status"=>false,"message"=>$msg]);
                //email does not exist
            }
        } catch (\Throwable $th) {
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    

    public function checkEmailForgotPassword(Request $request){

        try {

            $valdiation = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>401]);
            }

            $otp = rand(100000,999999);
            $checkEmail = $this->userService->checkEmail($request['email']);
            if($checkEmail){
                //check email verified or not
                if($checkEmail['is_varified'] == 1){
                    //update otp in user table and send otp
                    $updateOtp = $this->userService->insertNewOtp($otp,$checkEmail['id'],$checkEmail['email_verification_code'], $request['email']);
                    if($updateOtp){
                        $msg= __("api_string.send_verification_code");
                        return response()->json(["statusCode"=>200,"status"=>true,"message"=>$msg,'otp'=>$otp,"email"=>$request['email']]);
                    }
                }else{
                    $msg= __("api_string.verifie_your_email");
                    return response()->json(["statusCode"=>401,"status"=>false,"message"=>$msg]);
                }
            }else{
                $msg= __("api_string.email_exist");
                return response()->json(["statusCode"=>401,"status"=>false,"message"=>$msg]);
            }
        } catch (\Throwable $th) {
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }
    
    public function forgotPassword(Request $request){
        try {$valdiation = Validator::make($request->all(), [

                'email' => 'required|email',
                'otp' => 'required',
                'new_password' => 'required',
            ]);

            if($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>401]);
            }

            //check email
            $checkEmail = $this->userService->checkEmail($request['email']);
            if($checkEmail['is_varified'] == 1){
                //verify otp 
                $checkValidOtp = $this->userService->checkOtp($request);
                if($checkValidOtp){
                    $token = $this->userService->updatePassword($request,$checkEmail['id'],$checkEmail['email']);
                    $msg= __("api_string.reset_password");
                    return response()->json(["statusCode"=>201,"status"=>true,"message"=>$msg]);
                  
                }else{
                    $msg= __("api_string.incorrect_otp");
                    return response()->json(["statusCode"=>401,"status"=>true,"message"=>$msg]);
                }
            }else{
                $msg= __("api_string.email_exist");
                return response()->json(["statusCode"=>401,"status"=>false,"message"=>$msg]);
            }

        } catch (\Throwable $th) {
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }
    // public function forgetPasswords(Request $request)
    // {
    //     $valdiation = Validator::make($request->all(),[
    //         'email' => 'required|email',
    //     ]);
    //     if($valdiation->fails()) {
    //         $msg = __("api_string.invalid_fields");
    //         return response()->json(["message"=>$msg, "statusCode"=>401]);
    //     }
    //      $response = $this->userService->forgetPassword($request);
    //       if(($response)==true){
    //         $msg = __("api_string.forget_password_link");
    //             $response1 = [];
    //             $response1['message'] = $msg;
    //             $response1['statusCode'] =200;
    //             $response1['email'] =$request->email;
    //             return response()->json(["Email"=>$response1['email'],"statusCode"=>$response1['statusCode'],"status"=>true,"message"=>$response1['message']]);
    //     }
    //    elseif(($response)==false)
    //    {
    //     $msg = __("api_string.verifie_your_email");
    //     $response1 = [];
    //     $response1['message'] = $msg;
    //     $response1['statusCode'] =401;
    //     return response()->json(["statusCode"=>$response1['statusCode'],"status"=>true,"message"=>$response1['message']]);
    //    }
    //    else
    //    {
    
    //     $msg = __("api_string.email_not_exist");
    //     return response()->json(["Status"=>false,"message"=>$msg, "statusCode"=>401]);
    //    }
    // }
    
    public function getUserDetailsByID(Request $request){
        $user = Auth::user();
       
        return response()->json([
            'message' => ' currently logged in user',
            'user'=>$user],200);
    }
    public function updateUserDetails(Request $request){
        
        $response = $this->userService->updateData($request);
        return response()->json(['message'=>'data updated',
                                  'user'=>$user]);
    }

    


    public function signUpSecond(Request $request,$email='teest@yopmail.com' ) {

        
             $userCheck =  User::select('id','is_varified','email')->where('email',$email)->first();
        if(!empty($userCheck)){
          
            if($userCheck->is_varified ==0){
                return response()->json([
                    'message' => 'User email is not varified yet',
                    'role_id' => $userCheck->role_id,                           
                ], 400);
            }
            if($userCheck->is_varified ==1){
                       
                
               $valdiation = Validator::make($request->all(),[
                'store_name'=>'required|string',
                'telegram_id'=>'required|string',
                'whatsapplinks'=>'required|string',
                'whatsapp_links'=>'required|string',
                'Instagram_links'=>'required|string',
                'whatsapplinks'=>'required|string',
                'store_name'=>'required|string',
                ]
                 );


                if($valdiation->fails()){
                    return response()->json($valdiation->errors(), 202);
                }
                $user=new User();
                $user->store_name=$request->store_name;
                $user->telegram_id=$request->telegram_id;
                $user->whatsapplinks=$request->whatsapplinks;
                $user->Instagram_links=$request->Instagram_links;
                $user->role_id= $request->role_id;
                $user->save();
            }
          
            
        }
    }
    
    public function logout(Request $request){
        $response = $this->userService->checkLogOut($request);
        return $response;
    }
        
    public function checkLogin(Request $request){
        $response = $this->userService->checkLogin($request);
        return $response;
    }

    public function user_verification(Request $request){
        
        $response = $this->userService->user_verification($request);
        return $response;
    
     }

     public function sendVerificationEmail(Request $request){
        $response = $this->userService->sendVerificationEmail($request->$token);
        return $response;
        
        }   

        
    }
