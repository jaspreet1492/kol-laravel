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

class FeedbackController extends Controller
{
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    public function AddFeedback(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $endUserId = auth()->user()->id;
            if($roleId == 3){
                
                $valdiation = Validator::make($request->all(),[
                    'kol_profile_id' => 'required',
                    'rating' => 'nullable',
                    'comment' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }
                
                $kol_profile_id = $request['kol_profile_id'];    
                $checkKolUser = $this->userService->ViewKolProfileById($kol_profile_id);
                $kol_user_id = $checkKolUser[0]['user_id'];

                if($request['rating']>5){
                    $msg=__("api_string.rating_limit");
                    return response()->json(["status"=>true,'statusCode'=>401,"message"=>$msg]);
                }

                $addFeedback = $this->userService->AddFeedback($request,$endUserId,$kol_user_id);
                $msg=__("api_string.Feedback_added");
                return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);
                
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

    public function getEndUserFeedbackList(){
        try {
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            if($roleId == 3){
                $FeedbackList = $this->userService->getEndUserFeedbackList($userId);
                return response()->json(["status"=>true,"statusCode"=>200,"Feedbacks"=>$FeedbackList]);
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

    public function getKolFeedbackList(Request $request){
        try {
            $roleId = auth()->user()->role_id;
            $kolProfileId = $request['kol_profile_id'];
            $FeedbackList = $this->userService->getKolFeedbackList($kolProfileId);
            return response()->json(["status"=>true,"statusCode"=>200,"Feedbacks"=>$FeedbackList]);
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

}
