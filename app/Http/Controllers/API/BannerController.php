<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\Auth;
use App\Models\KolProfile;
use Validator;
use JWTAuth;

class BannerController extends Controller
{
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    public function AddBanner(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $endUserId = auth()->user()->id;
            if($roleId == 1){
                
                $valdiation = Validator::make($request->all(),[
                    'title' => 'required|alpha_num',
                    'description' => 'required|alpha_num',
                    'banner' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }

                $id = $request['id'];    
                $checkBanner = $this->userService->checkBannerExistOrNot($id);

                if($checkBanner){
                    //update banner
                    $checkProfile = $this->userService->UpdateBanner($request,$id);
                    $msg=__("api_string.banner_updated");
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
    
                }else{
                    //add banner
                    $addBanner = $this->userService->AddBanner($request,$endUserId);
                    $msg=__("api_string.banner_added");
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

    public function deleteBanner(Request $request){
        $checkBanner = $this->userService->checkBannerExistOrNot($request['id']);
        
        if($checkBanner){
            $BannerData = $this->userService->deleteBanner($request['id']);
            $statusCode= 200;
            $msg=__("api_string.banner_deleted");
        } else{
            $statusCode= 204;
            $msg=__("api_string.banner_already_deleted");
        }

        if($request['id']==''){
            $statusCode= 400;
            $msg=__("api_string.empty_request_id");
        }
        return response()->json(["status"=>true,'statusCode'=>$statusCode,"message"=>$msg]);
    }

    public function getBannerList(Request $request){

        $banners = $this->userService->getBannerList();
        return response()->json(["status"=>true,"statusCode"=>200,"banners"=>$banners]);
    }
}
