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

class BookmarkController extends Controller
{
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    public function AddBookmark(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            $endUserId = auth()->user()->id;
            if($roleId == 3){
                $valdiation = Validator::make($request->all(),[
                    'kol_profile_id' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }
                
                $kol_profile_id = $request['kol_profile_id'];    
                $checkKolUser = $this->userService->ViewKolProfileById($kol_profile_id);
                $checkBookmark = $this->userService->checkBookmarkExistOrNot($endUserId,$kol_profile_id);

                if(!$checkKolUser){
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Kol profile does not exist"]);
                }

                $kol_user_id = $checkKolUser[0]['user_id'];
                if(empty($checkBookmark)){
                    $addBookmark = $this->userService->AddBookmark($kol_profile_id,$endUserId,$kol_user_id);
                    $msg=__("api_string.bookmark_added");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);
                } else{
                    $msg=__("api_string.bookmarked_already");
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>$msg]);
                }

            }else{
                //Not Author ized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
            
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function deleteBookmark(Request $request){
        try {
            $roleId = auth()->user()->role_id;
            $endUserId = auth()->user()->id;

            if($roleId == 3){
                $kol_profile_id = $request['kol_profile_id']; 
                $checkBookmark = $this->userService->checkBookmarkExistOrNot($endUserId,$kol_profile_id);
                
                if($checkBookmark){
                    $bookmarkData = $this->userService->deleteBookmark($kol_profile_id,$endUserId);
                    $statusCode= 200;
                    $msg=__("api_string.bookmark_deleted");

                } else{
                    $statusCode= 204;
                    $msg=__("api_string.bookmark_already_deleted");
                }
                return response()->json(["status"=>true,'statusCode'=>$statusCode,"message"=>$msg]);
            }else{
                //Not Author ized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function getBookmarks(){
        try {
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            if($roleId == 3){
                $BookmarkList = $this->userService->getBookmarks($userId);
                return response()->json(["status"=>true,"statusCode"=>200,"bookmarks"=>$BookmarkList]);
            }else{
                //Not Author ized
                $msg=__("api_string.not_authorized");
                return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
            }

        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

}