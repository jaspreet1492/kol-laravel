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
                    'title' => 'required', 
                    'description' => 'required', 
                    'type' => 'required|in:image,video',
                    'total_days' => 'required', 
                    'price' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }
                
                $id = $request['id']; 
                $profileId = $KolProfile['id'];   
                
                $checkDealCount = $this->userService->DealCount($profileId);

                if($checkDealCount < 5){
                    if($KolProfile !=null){
                        if($request['id']){
                            // update deal
                            $UpdateDeal = $this->userService->UpdateDeal($request,$KolProfile);
                            $msg=__("api_string.deal_updated");
                            return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
                        } else{
                            //add deal
                            $addDeal = $this->userService->AddDeal($request,$KolProfile);
                            $msg=__("api_string.deal_added");
                            return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);     
                        }
                    } else{
                        return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Please add profile details first."]);
                    }
                } else {
                    return response()->json(["status"=>false,'statusCode'=>403,"message"=>"You cannot create more than 5 Deals."]);
                }
            }else{
                //Not Authorized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage(), 'trace'=>$th->getTraceAsString()]);
        }
    }

    public function getDealsById(Request $request){
        $id = $request['id'];
        $deals = $this->userService->getDealsById($id);
        return response()->json(["status"=>true,"statusCode"=>200,"deals"=>$deals]);
    }

    public function deleteDeal(Request $request){
        $userId = auth()->user()->id;
        $checkKolProfile = $this->userService->checkKolProfileExistOrNot($userId);

        $checkDeal = $this->userService->checkDealExistOrNot($request['id'],$checkKolProfile['id']);

        if($checkDeal){
            $dealData = $this->userService->deleteDeal($request['id']);
            $statusCode= 200;
            $msg=__("api_string.deal_deleted");
        } else{
            $statusCode= 204;
            $msg=__("api_string.deal_already_deleted");
        }
        return response()->json(["status"=>true,'statusCode'=>$statusCode,"message"=>$msg]);
    }


    public function requestDeal(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            $KolProfile= KolProfile::select('id')->where('user_id',$userId)->first(); 
    
            if($roleId == 3){
                $valdiation = Validator::make($request->all(),[
                    'title' => 'required', 
                    'description' => 'required', 
                    'type' => 'required|in:image,video',
                    'total_days' => 'required', 
                    'price' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }
                
                $id = $request['id']; 
                $profileId = $KolProfile['id'];   
                
                $checkDealCount = $this->userService->DealCount($profileId);

                if($checkDealCount < 5){
                    if($KolProfile !=null){
                        if($request['id']){
                            // update deal
                            $UpdateDeal = $this->userService->UpdateDeal($request,$KolProfile);
                            $msg=__("api_string.deal_updated");
                            return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
                        } else{
                            //add deal
                            $addDeal = $this->userService->AddDeal($request,$KolProfile);
                            $msg=__("api_string.deal_added");
                            return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);     
                        }
                    } else{
                        return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Please add profile details first."]);
                    }
                } else {
                    return response()->json(["status"=>false,'statusCode'=>403,"message"=>"You cannot create more than 5 Deals."]);
                }
            }else{
                //Not Authorized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage(), 'trace'=>$th->getTraceAsString()]);
        }
    }

}
