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


class AnnouncementController extends Controller
{
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    public function AddorUpdateAnnouncement(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            $profileId= KolProfile::where('user_id',$userId)->pluck('id');
            if($roleId == 2){
                $valdiation = Validator::make($request->all(),[
                    'title' => 'required', 
                    'description' => 'required', 
                    'start_date' => 'required', 
                    'end_date' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }

                $id = $request['id'];    
                $checkAnnouncement = $this->userService->checkAnnouncementExistOrNot($id);
               
                if($checkAnnouncement){
                    // update announcement
                    $checkProfile = $this->userService->UpdateAnnouncement($request,$id);
                    $msg=__("api_string.announcement_updated");
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
    
                }else{
                    //add  announcement
                    $addAnnouncement = $this->userService->AddAnnouncement($request,$userId,$profileId[0]);
                    $msg=__("api_string.announcement_added");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);
    
                }
            }else{
                //Not Author ized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>true,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }
}
