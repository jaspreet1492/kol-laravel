<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Validator;
use JWTAuth;
class KolProfileController extends Controller
{
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }
    public function AddOrUpdateKolProfile(Request $request){
        
        try {
            $roleId = auth()->user()->role_id;
            if($roleId == 2){
                $valdiation = Validator::make($request->all(),[
                    'languages' => 'required', 
                    'kol_type' => 'required', 
                    'state' => 'required', 
                    'zip_code' => 'required', 
                    'city' => 'required', 
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }
    
                $userId = auth()->user()->id;
                $checkProfile = $this->userService->checkKolProfileExistOrNot($userId);
               
                if($checkProfile){
                    // update profile
                    $checkProfile = $this->userService->UpdateKolProfile($request,$userId);
                    $msg=__("api_string.kol_profile_updated");
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
    
                }else{
                    //add  profile
                    $checkProfile = $this->userService->AddKolProfile($request,$userId);
                    $msg=__("api_string.kol_profile_added");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);
    
                }
            }else{
                //Not Authorized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>true,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function getKolProfileById(Request $request){
        
        $kolProfileData = $this->userService->ViewKolProfileById($request['id']);
        return response()->json(["status"=>true,"statusCode"=>200,"kolProfile"=>$kolProfileData]);
    }

    public function getProfileList(Request $request){
        $kolProfiles = $this->userService->KolProfileList($request);
        return response()->json(["status"=>true,"statusCode"=>200,"kolProfiles"=>$kolProfiles]);
    }
}
