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
            $checkProfile = $this->userService->checkKolProfileExistOrNot($request,$userId);
            if($checkProfile){
               // update profile

            }else{
                //add  profile
            $checkProfile = $this->userService->AddKolProfile($request,$userId);

            }
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }
}
