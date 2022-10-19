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
    
    public function saveProfileView(Request $request)
    {
    try{
        $roleId = auth()->user()->role_id;
        $userId = auth()->user()->id;
        $valdiation = Validator::make($request->all(),[
            'profile_id' => 'required', 
        ]);
        if($valdiation->fails()) {
            $msg = __("api_string.invalid_fields");
            return response()->json(["message"=>$msg, "statusCode"=>422]);
        }
        if($request['kol_id']!= $userId){
            $checkProfile = $this->userService->checkKolProfileIdExistOrNot($request['profile_id']);
            if($checkProfile!=null){
                $response = $this->userService->updateViewCount($request,$userId,$checkProfile->total_viewer);
                $msg=__("api_string.profile_view_updated");
                return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);

            }else{
                $msg= __("api_string.error");
                return response()->json(["statusCode"=>422,"status"=>false,"message"=>$msg]);
            }
           
        }else{
            $msg= __("api_string.error");
                return response()->json(["statusCode"=>422,"status"=>false,"message"=>$msg]);
        }

        }catch (\Throwable $th) {
            
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function AddOrUpdateKolProfile(Request $request){
        
        try {
            $roleId = auth()->user()->role_id;
            if(!is_array($request['social_media'][0])){
                $newSocialMedia = json_decode($request['social_media'][0], true);            
                $request->merge(array('social_media'=>$newSocialMedia));
            }
            if($roleId == 2){
                $valdiation = Validator::make($request->all(),[
                    'languages.*' => 'required', 
                    'kol_type' => 'required', 
                    'state' => 'required', 
                    'zip_code' => 'required', 
                    'city' => 'required|alpha', 
                    'bio' => 'required',
                    'avatar' => 'nullable|mimes:png,jpeg,jpg',
                    'banner' => 'nullable|mimes:png,jpeg,jpg',
                    'video_links.*'=>'required|url',
                    'tags.*' => 'required',
                    'personal_email' => 'nullable|email',
                    'social_active'=>'required',
                    'social_media.*.name'=>'required',
                    'social_media.*.social_user_id'=>'required',
                    'social_media.*.followers'=>'required',
                ]);
                if($valdiation->fails()) {
                    $msg = $valdiation->errors()->first();
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }
                
                $langauges = Config('app.languages');
                $states = Config('app.states');
                $streams = Config('app.stream');
                $kolTypes = $this->userService->ViewKolType($request['kol_type']);
                // $countLang = count(array_intersect($langauges,$request['languages']));
                $countstream = in_array($request['social_active'],$streams);
                $countsocial = count(array_intersect($streams,array_column($request['social_media'],'name')));
                $stateCheck = in_array($request['state'],$states);

                // if($countLang !== count($request['languages'])){
                //     $msg=__("api_string.valid_langauge");
                //     return response()->json(["status"=>false,'statusCode'=>301,"message"=>$msg]);
                // }
                if(!$countstream){
                    $msg=__("api_string.valid_stream");
                    return response()->json(["status"=>false,'statusCode'=>301,"message"=>$msg]);
                }
                if($countsocial !== count(array_column($request['social_media'],'name'))){
                    $msg=__("api_string.social_media_name");
                    return response()->json(["status"=>false,'statusCode'=>301,"message"=>$msg]);
                }
                if(!$stateCheck){
                    $msg=__("api_string.valid_state");
                    return response()->json(["status"=>false,'statusCode'=>301,"message"=>$msg]);
                }
                if(!$kolTypes){
                    $msg=__("api_string.valid_kol_type");
                    return response()->json(["status"=>false,'statusCode'=>301,"message"=>$msg]);
                };
                
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
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }


    public function FeatureKolProfile(Request $request){
        
        try {
            $roleId = auth()->user()->role_id;
            if($roleId == 1){
                $valdiation = Validator::make($request->all(),[
                    'kol_profile_id' => 'required',
                    'is_featured' => 'required|in:1,0'
                ]);

                if($valdiation->fails()) {
                    $msg = $valdiation->errors()->first();
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }
                
                $kolProfileData = $this->userService->checkKolProfileIdExistOrNot($request['kol_profile_id']);
                
                if(!$kolProfileData){
                    return response()->json(["statusCode"=>422,"status"=>false,"message"=>"kol profile does not exist"]);
                }

                if($request['is_featured'] == 1){
                    // feature kol profile
                    $checkProfile = $this->userService->FeatureKolProfile($request);
                    $msg=__("api_string.featured_profile");
                } 
                if($request['is_featured'] == 0){
                    // unfeature kol profile
                    $checkProfile = $this->userService->FeatureKolProfile($request);
                    $msg=__("api_string.unfeatured_profile");    
                    
                }
                
                return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
            }else{
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function getKolProfileById(Request $request){
        
        $endUserId = auth()->user()->id;

        $kolProfileData = $this->userService->ViewKolProfileById($request['id']);
        $checkBookmark = $this->userService->checkBookmarkExistOrNot($endUserId,$request['id']);
        // dd($checkBookmark);
        if(empty($checkBookmark)){
            $kolProfileData[0]['bookmark']= false;
        }else {
            $kolProfileData[0]['bookmark'] = true;
        }
        return response()->json(["status"=>true,"statusCode"=>200,"kolProfile"=> $kolProfileData]);
    }

    public function getKolProfile(Request $request){
        
        $endUserId = auth()->user()->id;
        $kolProfileData = $this->userService->checkKolProfileExistOrNot($endUserId);
        if($kolProfileData){
            $checkBookmark = $this->userService->checkBookmarkExistOrNot($endUserId,$kolProfileData['id']);
            
            if(empty($checkBookmark)){
                $kolProfileData[0]['bookmark']= false;
            }else {
                $kolProfileData[0]['bookmark'] = true;
            }
            return response()->json(["status"=>true,"statusCode"=>200,"kolProfile"=> $kolProfileData]);
        } else{
            return response()->json(["status"=>true,"statusCode"=>200, "kolProfile"=> 0, "msg" => "Please add profile details first."]);
        }
    }

    public function getProfileList(Request $request){
        try {
            $roleId = auth()->user()->role_id;
            if($roleId == 2 || $roleId ==3){
                $kolProfiles = $this->userService->KolProfileList($request);
            }else{
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }

        return response()->json(["status"=>true,"statusCode"=>200,"kolTotalProfiles"=>$kolProfiles['total'],"kolProfiles"=>$kolProfiles['data']]);
    }

    public function getFeaturedProfileList(Request $request){
        $kolProfiles = $this->userService->getFeaturedProfileList($request);
        return response()->json(["status"=>true,"statusCode"=>200,"kolProfiles"=>$kolProfiles]);
    }
}
