<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Validator;
use JWTAuth;

class KolTypeController extends Controller
{
    private $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    public function AddorUpdateKolType(Request $request){

        try {
            $roleId = auth()->user()->role_id;
            if($roleId == 1){
                $valdiation = Validator::make($request->all(),[
                    'name' => 'required|unique:kol_type|max:255',
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.unique_kol_type");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }

                $checkKolType = $this->userService->checkKolTypeExistOrNot($request['id']);
                if($checkKolType){
                    // update KolType
                    $checkProfile = $this->userService->UpdateKolType($request,$request['id']);
                    $msg=__("api_string.kol_type_updated");
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);
    
                }else{
                    //add  KolType
                    $addKolType = $this->userService->AddKolType($request);
                    $msg=__("api_string.kol_type_added");
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

    public function getKolTypeList(Request $request){

        $kolTypes = $this->userService->KolTypeList($request);
        
        return response()->json(["status"=>true,"statusCode"=>200,"kol_types"=>$kolTypes]);

    }

    public function ActiveInactiveKolType(Request $request){
        
        try {
            $roleId = auth()->user()->role_id;
            if($roleId == 1){
                $valdiation = Validator::make($request->all(),[
                    'id' => 'required',
                    'status' => 'required'
                ]);
                if($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message"=>$msg, "statusCode"=>422]);
                }

                $id = $request['id'];    
                $status = $request['status'];  
                $getCurrentStatus = $this->userService->getKolTypestatus($id);
                $checkKolType = $this->userService->checkKolTypeExistOrNot($id);
                if($request['status'] == 'active'){
                    $status = 1;
                }elseif($request['status']=='inactive'){
                    $status = 0;
                }
                
                if($checkKolType){
                    $changeStatus = $this->userService->ActiveInactiveKolType($request['id'],$status);
                    if($status==1 && $status != $getCurrentStatus){
                        $msg = 'KolType Activated';
                    } elseif($status==0 && $status != $getCurrentStatus){
                        $msg = 'KolType Deactivated';
                    } elseif($status == $getCurrentStatus){
                        $msg = 'Action cannot be performed';
                    }
                    return response()->json(["status"=>true,'statusCode'=>202,"message"=>$msg]);

                }else{
                    return response()->json(["status"=>true,'statusCode'=>401,"message"=>'KolType Not Found']);
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

    public function getLanguage(){
        try {
            $languages = Config('app.languages');
            $msg=__("api_string.language_list");
            return response()->json(["status"=>true,'statusCode'=>200,"message"=>$msg,"data"=>(object)$languages]);
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function getState(){
        try {
            $languages = Config('app.states');
            $msg=__("api_string.state_list");
            return response()->json(["status"=>true,'statusCode'=>200,"message"=>$msg,"data"=>(object)$languages]);
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function getStream(){
        try {
            $languages = Config('app.stream');
            $msg=__("api_string.stream_list");
            return response()->json(["status"=>true,'statusCode'=>200,"message"=>$msg,"data"=>(object)$languages]);
        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

}
