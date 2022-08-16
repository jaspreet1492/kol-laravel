<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\ChatThread;

class ChatController extends Controller
{
    private $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendMessage(Request $request)
    {
        try {

            $roleId = auth()->user()->role_id;
            // die('cc');
            $userId = auth()->user()->id;
            $valdiation = Validator::make($request->all(), [
                'receiver_id' => 'required',
                'message' => 'required',
            ]);
            if ($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message" => $msg, "statusCode" => 422]);
            }
            
            $userCheck = $this->userService->getUserById($request['receiver_id']);
            if(!$userCheck){
                return response()->json(["statusCode" => 422, "status" => false, "message" => 'user not found']);
            }
 


            if ($roleId == 2 || $roleId == 3) {
                $receiverData = $this->userService->getUserRoleById($request['receiver_id']);
                if( $receiverData->role_id == 2){
                    $checkKolProfileReceiver = $this->userService->checkKolProfileExistOrNot($request['receiver_id']);
                    if(!$checkKolProfileReceiver){
                        return response()->json(["statusCode" => 422, "status" => false, "message" => 'invalid request']);
                    }
                }
                if($roleId == 2){
                    $checkKolProfileUser = $this->userService->checkKolProfileExistOrNot($userId);
                    if(!$checkKolProfileUser){
                        return response()->json(["statusCode" => 422, "status" => false, "message" => 'invalid request']);
                    }
                }
                if ($receiverData) {
                    if($receiverData->role_id!=1){
                        if ($receiverData->role_id == $roleId ) {
                            return response()->json(["statusCode" => 422, "status" => false, "message" => 'invalid user']);
                        } else {
                            $response = $this->userService->saveChat($request, $userId);
                            if ($response === true) {
                                $msg = __("api_string.msg_sent");
                                return response()->json(["status" => true, 'statusCode' => 201, "message" => $msg]);
                            } else {
                                $msg = __("api_string.error");
                                return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
                            }
                        }
                    }else{
                        return response()->json(["statusCode" => 422, "status" => false, "message" => 'You are not authorize to send message to this user']);
                    }
                    
                } else {
                    return response()->json(["statusCode" => 422, "status" => false, "message" => 'user not found']);
                }
            } else {
                $msg = __("api_string.not_authorized");
                return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
            }
        } catch (\Throwable $th) {

            $msg = __("api_string.error");
            return response()->json(["statusCode" => 500, "status" => false, "message" => $th->getMessage()]);
        }
    }

    public function getChatData(Request $request)
    {
        try {
            $valdiation = Validator::make($request->all(), [
                'receiver_id' => 'required',

            ]);
            if ($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message" => $msg, "statusCode" => 422]);
            }
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            $userCheck = $this->userService->getUserById($request['receiver_id']);
            if(!$userCheck){
                return response()->json(["statusCode" => 422, "status" => false, "message" => 'user not found']);
            }
            if ($roleId == 2 || $roleId == 3) {
                $response = $this->userService->getChat($request, $userId);
                if (!empty($response)) {
                    $msg = __("api_string.msg_get");
                    return response()->json(["status" => true, 'statusCode' => 201, "data" => $response, "message" => $msg]);
                } else {
                    $msg = __("api_string.error");
                    return response()->json(["statusCode" => 404, "data" => [], "status" => false]);
                }
            } else {
                $msg = __("api_string.not_authorized");
                return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
            }
        } catch (\Throwable $th) {

            $msg = __("api_string.error");
            return response()->json(["statusCode" => 500, "status" => false, "message" => $th->getMessage()]);
        }
    }

    public function getChatDataUsers(Request $request)
    {
        try {
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            if ($roleId == 2 || $roleId == 3) {
                // die('cc');
                $response = $this->userService->getChatUsers($request, $userId);
                if (!empty($response)) {
                    $msg = __("api_string.msg_get");
                    return response()->json(["status" => true, 'statusCode' => 201, "data" => $response, "message" => $msg]);
                } else {
                    $msg = __("api_string.error");
                    return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
                }
            } else {
                $msg = __("api_string.not_authorized");
                return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
            }
        } catch (\Throwable $th) {

            $msg = __("api_string.error");
            return response()->json(["statusCode" => 500, "status" => false, "message" => $th->getMessage()]);
        }
    }

    public function deleteChat(Request $request)
    {
        try {
            $valdiation = Validator::make($request->all(), [
                'msg_id' => 'required',

            ]);
            if ($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message" => $msg, "statusCode" => 422]);
            }
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            $msgCheck = $this->userService->getMsgById($request['msg_id'],$userId);
            if(!$msgCheck){
                return response()->json(["statusCode" => 422, "status" => false, "message" => 'Message not found']);
            }
            if ($roleId == 2 || $roleId == 3) {
                $response = $this->userService->deleteMsg($request, $userId);

                if ($response) {
                    $msg = __("api_string.msg_delete");
                    return response()->json(["status" => true, 'statusCode' => 201, "message" => $msg]);
                } else {
                    $msg = __("api_string.error");
                    return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
                }
            } else {
                $msg = __("api_string.not_authorized");
                return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
            }
        } catch (\Throwable $th) {

            $msg = __("api_string.error");
            return response()->json(["statusCode" => 500, "status" => false, "message" => $th->getMessage()]);
        }
    }
    public function editChat(Request $request)
    {
        try {
            $valdiation = Validator::make($request->all(), [
                'msg_id' => 'required',
                'message' => 'required',
            ]);
            if ($valdiation->fails()) {
                $msg = __("api_string.invalid_fields");
                return response()->json(["message" => $msg, "statusCode" => 422]);
            }
            $roleId = auth()->user()->role_id;
            $userId = auth()->user()->id;
            $msgCheck = $this->userService->getMsgById($request['msg_id'],$userId);
            if(!$msgCheck){
                return response()->json(["statusCode" => 422, "status" => false, "message" => 'Message not found']);
            }
            if ($roleId == 2 || $roleId == 3) {
                $response = $this->userService->editMsg($request, $userId);
                if ($response) {
                    $msg = __("api_string.msg_edit");
                    return response()->json(["status" => true, 'statusCode' => 201, "message" => $msg]);
                } else {
                    $msg = __("api_string.error");
                    return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
                }
            } else {
                $msg = __("api_string.not_authorized");
                return response()->json(["statusCode" => 422, "status" => false, "message" => $msg]);
            }
        } catch (\Throwable $th) {

            $msg = __("api_string.error");
            return response()->json(["statusCode" => 500, "status" => false, "message" => $th->getMessage()]);
        }
    }
}
