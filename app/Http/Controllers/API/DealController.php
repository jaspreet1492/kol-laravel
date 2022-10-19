<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\Auth;
use App\Models\KolProfile;
use App\Models\User;
use Validator;
use JWTAuth;

class DealController extends Controller
{ 
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    public function AddorUpdateDeal(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            $KolProfile= KolProfile::select('id')->where('user_id',$userId)->first(); 
    
            if($roleId == 2){
                $valdiation = Validator::make($request->all(),[
                    'title' => 'required|regex:/^[a-z0-9 .,]+$/i', 
                    'description' => 'required|regex:/^[a-z0-9 .,]+$/i', 
                    'type' => 'required|in:image,video',
                    'total_days' => 'required|integer', 
                    'price' => 'required|integer' 
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }
                
                $id = $request['id']; 
                

    
                    if($KolProfile !=null){
                        $profileId = $KolProfile['id'];   
                
                        $checkDealCount = $this->userService->DealCount($profileId);
                        if($request['id']){
                            // update deal
                            $UpdateDeal = $this->userService->UpdateDeal($request,$KolProfile);
                            $msg=__("api_string.deal_updated");
                            return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
                        } else{
                            //add deal
                            if($checkDealCount < 5){
                                $addDeal = $this->userService->AddDeal($request,$KolProfile);
                                $msg=__("api_string.deal_added");
                                return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);  
                            } else {
                                return response()->json(["status"=>false,'statusCode'=>403,"message"=>"You cannot create more than 5 Deals."]);
                            }   
                        }
                    } else{
                        return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Please add profile details first."]);
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

    public function getDealsById(Request $request){
        try {
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            $checkKolProfile = $this->userService->checkKolProfileExistOrNot($userId);
            
            if($roleId == 2){
                $id = $request['id'];
                $kolProfileId = $checkKolProfile['id'];
                $deal = $this->userService->getDealsById($id,$kolProfileId);
                if($deal){
                    return response()->json(["status"=>true,"statusCode"=>200,"deals"=>$deal]);
                } else {
                    return response()->json(["status"=>true,"statusCode"=>200,"deals"=>"No deal available"]);
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

    public function getDealsListByKolProfileId(Request $request){
        
        try {
            $userId = auth()->user();
            $roleId = auth()->user()->role_id;

            if($userId){
                $kolProfileId = $request['kol_profile_id'];
                $deals = $this->userService->getDealsListByKolProfileId($kolProfileId);
                return response()->json(["status"=>true,"statusCode"=>200,"deals"=>$deals]);
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

    public function getDealsListByKolUserId(Request $request){
        
        try {
            $userId = auth()->user();

            if($userId){
                $kolUserId = $request['kol_user_id'];
                $checkKolProfile = $this->userService->checkKolProfileExistOrNot($kolUserId);
                if($checkKolProfile){
                    $deals = $this->userService->getDealsListByKolProfileId($checkKolProfile['id']);
                    if($deals){
                        return response()->json(["status"=>true,"statusCode"=>200,"deals"=>$deals]);
                    } else {
                        return response()->json(["status"=>true,'statusCode'=>201,"deals"=>[]]);
                    }
                } else {
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Kol profile does not exist"]);
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

    public function getDealsListByLoggedInKolUser(Request $request){
        try {
            $roleId = auth()->user()->role_id;
            if($roleId == 2){
                $userId = auth()->user()->id;
                $checkKolProfile = $this->userService->checkKolProfileExistOrNot($userId);
                if($checkKolProfile){
                    $kolProfileId = $request['kol_profile_id'];
                    $deals = $this->userService->getDealsListByKolProfileId($checkKolProfile['id']);
                    return response()->json(["status"=>true,"statusCode"=>200,"deals"=>$deals]);
                } else {
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Please add profile details first."]);
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

    public function deleteDeal(Request $request){
        $userId = auth()->user()->id;
        $checkKolProfile = $this->userService->checkKolProfileExistOrNot($userId);

        if($checkKolProfile){
            $checkDeal = $this->userService->checkDealExistOrNot($request['id'],$checkKolProfile['id']);

            if($checkDeal){
                $dealData = $this->userService->deleteDeal($request['id'],$checkKolProfile['id']);
                $statusCode= 200;
                $msg=__("api_string.deal_deleted");
            } else{
                $statusCode= 204;
                $msg=__("api_string.deal_already_deleted");
            }
            return response()->json(["status"=>true,'statusCode'=>$statusCode,"message"=>$msg]);
        } else{
            //Not Authorized
            $msg=__("api_string.not_authorized");
            return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
        }

        
    }


    // public function requestDeal(Request $request){

    //     try {
    //         $roleId = auth()->user()->role_id;
    //         $userId = auth()->user()->id;
    
    //         if($roleId == 3){
    //             $valdiation = Validator::make($request->all(),[
    //                 'kol_profile_id' => 'required'
    //             ]);
    //             if($valdiation->fails()) {
    //                 $msg = __("api_string.invalid_fields");
    //                 return response()->json(["message"=>$msg, "statusCode"=>422]);
    //             }

    //             $kol_profile_id = $request['kol_profile_id']; 
    //             $KolProfile= KolProfile::where('id',$kol_profile_id)->where('status', 1)->with('getUser')->whereHas('getUser', function($query) use($request) {
    //                 $query->where('role_id', '=', 2); })->first();
    //             if($KolProfile){
    //                 $checkDealCount = $this->userService->DealCount($kol_profile_id);
                
    //                 if($checkDealCount == null || $checkDealCount == ''){
    //                     if($KolProfile !=null){
    //                         //request kol to create deal
    //                         $addDeal = $this->userService->requestDeal($request,$userId);
    //                         return response()->json(["status"=>true,'statusCode'=>201,"message"=>'Request Sent']);   
    //                     } else{
    //                         return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Please add profile details first."]);
    //                     }
    //                 } else {
    //                     return response()->json(["status"=>false,'statusCode'=>403,"message"=>"Kol already has deals."]);
    //                 }
                    
    //             } else {
    //                 return response()->json(["status"=>false,'statusCode'=>403,"message"=>"Enter Valid Kol id."]);
    //             }  

    //         }else{
    //             //Not Authorized
    //             $msg=__("api_string.not_authorized");
    //             return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
    //         }
            
    //     } catch (\Throwable $th) {
    //         $msg= __("api_string.error");
    //         return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
    //     }
    // }

    // public function watchDeal(Request $request){

    //     try {
    //         $roleId = auth()->user()->role_id;
    //         $kolUserId = auth()->user()->id;
    
    //         if($roleId == 2){
    //             $valdiation = Validator::make($request->all(),[
    //                 'id' => 'required',
    //                 'end_user_id' => 'required' 
    //             ]);
    //             if($valdiation->fails()) {
    //                 $msg = __("api_string.invalid_fields");
    //                 return response()->json(["message"=>$msg, "statusCode"=>422]);
    //             }

    //             $end_user_id = $request['end_user_id']; 
    //             $User= User::where('id',$end_user_id)->where('role_id',3)->where('status', 1)->first(); 
                
    //             if($User){
    //                 if($User !=null){
    //                     //watch the deal request
    //                     $addDeal = $this->userService->watchDeal($request,$kolUserId);
    //                     return response()->json(["status"=>true,'statusCode'=>201,"message"=>'Success']);   
    //                 } else{
    //                     return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Please add profile details first."]);
    //                 }
                    
    //             } else {
    //                 return response()->json(["status"=>false,'statusCode'=>403,"message"=>"Enter Valid User id"]);
    //             }  

    //         }else{
    //             //Not Authorized
    //             $msg=__("api_string.not_authorized");
    //             return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
    //         }
            
    //     } catch (\Throwable $th) {
    //         $msg= __("api_string.error");
    //         return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
    //     }
    // }

}
