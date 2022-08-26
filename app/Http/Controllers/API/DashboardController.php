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

class DashboardController extends Controller
{
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    public function AddUpdateInformativeVideo(Request $request){

        try {
            $roleId = auth()->user()->role_id;

            if($roleId == 1){
                
                $valdiation = Validator::make($request->all(),[
                    'title' => 'nullable|regex:/^[a-z0-9 .]+$/i',
                    'description' => 'nullable|regex:/^[a-z0-9 .]+$/i',
                    'banner' => 'required|url'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }

                $id = $request['id'];    
                $checkInformativeVideo = $this->userService->checkInformativeVideoExistOrNot($id);
                
                if($checkInformativeVideo){
                    //update banner
                    $checkProfile = $this->userService->UpdateInformativeVideo($request,$id);
                    $msg=__("api_string.information_updated");
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
    
                } elseif(!$checkInformativeVideo&&$request['id']) {
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>"Information does not exist"]);
                } else{
                    //add banner
                    $addBanner = $this->userService->AddInformativeVideo($request);
                    $msg=__("api_string.information_added");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);
                }

            }else{
                //Not Authorized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function deleteInformativeVideo(Request $request){
        $checkInformativeVideo = $this->userService->checkInformativeVideoExistOrNot($request['id']);
        
        if($checkInformativeVideo){
            $BannerData = $this->userService->deleteInformativeVideo($request['id']);
            $statusCode= 200;
            $msg=__("api_string.information_deleted");
        } else{
            $statusCode= 204;
            $msg=__("api_string.information_already_deleted");
        }
        
       
        return response()->json(["status"=>true,'statusCode'=>$statusCode,"message"=>$msg]);
    }

    public function getInformativeVideoList(Request $request){

        $InformativeVideoList = $this->userService->getInformativeVideoList();
        return response()->json(["status"=>true,"statusCode"=>200,"InformativeVideos"=>$InformativeVideoList]);
    }

    public function getTotalCount(Request $request){

        $TotalUsers = $this->userService->getTotalUsers();
        $TotalKol = $this->userService->getTotalKol();
        $TotalVideos = $this->userService->getTotalVideos();
        $TotalData = [
            'TotalUsers' => $TotalUsers, 
            'TotalKolUsers' => $TotalKol, 
            'TotalVideos' => $TotalVideos
        ];
        return response()->json(["status"=>true,"statusCode"=>200,"InformativeVideos"=>$TotalData]);
    }

    public function contactUs(Request $request){

        try {
        
            $valdiation = Validator::make($request->all(),[
                'email' => 'required|email',
                'messsage' => 'required'
            ]);
            if($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message"=>$msg, "statusCode"=>422]);
            }
            
            //send Query
            $contactData = $this->userService->contactUs($request);
            return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Email has been sent successfully"]);


        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }

    }

    public function AddUpdateBanner(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $endUserId = auth()->user()->id;
            if($roleId == 1){
                
                $valdiation = Validator::make($request->all(),[
                    'title' => 'required|regex:/^[a-z0-9 .]+$/i',
                    'description' => 'required|regex:/^[a-z0-9 .]+$/i',
                    'banner' => 'required|mimes:png,jpeg,jpg'
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
    
                } elseif(!$checkBanner&&$request['id']) {
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>"Banner does not exist"]);
                } else{
                    //add banner
                    $addBanner = $this->userService->AddBanner($request,$endUserId);
                    $msg=__("api_string.banner_added");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);
                }

            }else{
                //Not Authorized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function AddUpdateFaq(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $endUserId = auth()->user()->id;

            if($roleId == 1){
                
                $valdiation = Validator::make($request->all(),[
                    'question' => 'required',
                    'answer' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }

                $id = $request['id'];    
                $checkFaq = $this->userService->checkFaqExistOrNot($id);

                if($checkFaq){
                    //update Faq
                    $updateFaq = $this->userService->UpdateFaq($request,$id);
                    $msg=__("api_string.faq_updated");
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
    
                } elseif(!$checkFaq && $request['id']) {
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>"FAQ does not exist"]);
                } else{
                    //add Faq
                    $addFaq = $this->userService->AddFaq($request);
                    $msg=__("api_string.faq_added");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);
                }

            }else{
                //Not Authorized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
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

        return response()->json(["status"=>true,'statusCode'=>$statusCode,"message"=>$msg]);
    }

    public function deleteFaq(Request $request){
        $checkFaq = $this->userService->checkFaqExistOrNot($request['id']);
    
        if($checkFaq){
            $FaqData = $this->userService->deleteFaq($request['id']);
            $statusCode= 200;
            $msg=__("api_string.faq_deleted");
        } elseif(!$checkFaq && $request['id']) {
            return response()->json(["status"=>true,'statusCode'=>202,"message"=>"FAQ does not exist"]);
        } else{
            $statusCode= 204;
            $msg=__("api_string.faq_already_deleted");
        }

        return response()->json(["status"=>true,'statusCode'=>$statusCode,"message"=>$msg]);
    }

    public function getBannerList(Request $request){

        $banners = $this->userService->getBannerList();
        return response()->json(["status"=>true,"statusCode"=>200,"banners"=>$banners]);
    }

    public function getFaqList(Request $request){

        $faqs = $this->userService->getFaqList();
        return response()->json(["status"=>true,"statusCode"=>200,"banners"=>$faqs]);
    }

}
