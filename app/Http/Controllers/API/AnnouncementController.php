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
            $profileId= KolProfile::select('id')->where('user_id',$userId)->first();      
            if($roleId == 2){
                $valdiation = Validator::make($request->all(),[
                    'title' => 'required', 
                    'description' => 'required', 
                    'start_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:', 
                    'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:',
                    'image' => 'required|mimes:png,jpeg,jpg', 
                    'social_platform' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }

                $id = $request['id'];    

                $checkAnnouncement = $this->userService->checkAnnouncementExistOrNot($id,$userId);
                $socialmediacheck = in_array($request['social_platform'],Config('app.stream'));

                if(!$socialmediacheck){
                    $msg=__("api_string.valid_stream");
                    return response()->json(["status"=>false,'statusCode'=>301,"message"=>$msg]);
                }

                if($profileId !=null){
                    if($checkAnnouncement && $request['id']){
                        // update announcement
                        $checkProfile = $this->userService->UpdateAnnouncement($request,$id);
                        $msg=__("api_string.announcement_updated");
                        return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
                    } elseif(!$checkAnnouncement&&$request['id']) {
                        return response()->json(["status"=>true,'statusCode'=>202,"message"=>"Announcement does not exist"]);
                    }else{
                        //add  announcement
                        $addAnnouncement = $this->userService->AddAnnouncement($request,$userId,$profileId['id']);
                        $msg=__("api_string.announcement_added");
                        return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);     
                    }
                } else{
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Please add profile details first."]);
                }
                

            }else{
                //Not Author ized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage(), 'trace'=>$th->getTraceAsString()]);
        }
    }

    public function getAnnouncementById(Request $request){
        $AnnouncementData = $this->userService->ViewAnnouncementById($request['id']);
        return response()->json(["status"=>true,"statusCode"=>200,"announcement"=>$AnnouncementData]);
    }

    public function getAnnouncementListByKolUserId(Request $request){
        $userId = $request['id'];
        $announcements = $this->userService->getAnnouncementListByKolUserId($userId);
        return response()->json(["status"=>true,"statusCode"=>200,"announcements"=>$announcements]);
    }

    public function getAnnouncementList(Request $request){
        $userId = auth()->user()->id;
        $announcements = $this->userService->getAnnouncementList($userId);
        return response()->json(["status"=>true,"statusCode"=>200,"announcements"=>$announcements]);
    }

    public function getAllAnnouncementList(Request $request){
        $announcements = $this->userService->getAllAnnouncementList();
        return response()->json(["status"=>true,"statusCode"=>200,"announcements"=>$announcements]);
    }

    public function deleteAnnouncement(Request $request){
        $checkAnnouncement = $this->userService->checkAnnouncementExistOrNot($request['id'],$userId);
        if($checkAnnouncement){
            $announcementData = $this->userService->deleteAnnouncement($request['id']);
            $statusCode= 200;
            $msg=__("api_string.announcement_deleted");
        } else{
            $statusCode= 204;
            $msg=__("api_string.announcement_already_deleted");
        }
        return response()->json(["status"=>true,'statusCode'=>$statusCode,"message"=>$msg]);
    }

    public function AnnouncementActiveInactive(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            if($roleId == 2){
                $valdiation = Validator::make($request->all(),[
                    'status' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }

                $id = $request['id'];    
                $status = $request['status'];
                if(!$id){
                    return response()->json(["status"=>true,'statusCode'=>401,"message"=>'Please enter id']);
                }  
                $getCurrentStatus = $this->userService->getAnnouncementstatus($id);
                $checkAnnouncement = $this->userService->checkAnnouncementExistOrNot($id,$userId);
                if($request['status'] == 'active'){
                    $status = 1;
                }elseif($request['status']=='inactive'){
                    $status = 0;
                }
                if($checkAnnouncement){
                    $changeStatus = $this->userService->AnnouncementActiveInactive($request['id'],$status);
                    if($status==1 && $status != $getCurrentStatus){
                        $msg = 'Announcement Activated';
                    } elseif($status==0 && $status != $getCurrentStatus){
                        $msg = 'Announcement Deactivated';
                    } elseif($status == $getCurrentStatus){
                        $msg = 'Action cannot be performed';
                    }
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);

                }else{
                    return response()->json(["status"=>true,'statusCode'=>401,"message"=>'Announcement Not Found']);
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
    
}
