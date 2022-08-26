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

class OrderController extends Controller
{

    private $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function placeOrder(Request $request)
    {

        try {
            $roleId = auth()->user()->role_id;
            $end_user_id = auth()->user()->id;

            if ($roleId == 3) {    
                $valdiation = Validator::make($request->all(), [
                    'deal_id' => 'required|integer',
                    'kol_profile_id' => 'required|integer',
                    'start_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:',
                ]);
                if ($valdiation->fails()) {
                    $msg = __("api_string.invalid_fields");
                    return response()->json(["message" => $msg, "statusCode" => 422]);
                }
                $checkKolProfile = $this->userService->checkKolProfileIdExistOrNot($request['kol_profile_id']);
                
                if ($checkKolProfile) {
                    $checkDeal = $this->userService->checkDealExistOrNot($request['deal_id'], $request['kol_profile_id']);
                    if ($checkDeal) {
                        $placeOrder = $this->userService->placeOrder($request, $end_user_id,$checkDeal);
                        $msg=__("api_string.order_placed");
                        return response()->json(["status"=>true,'statusCode'=>201, "orderPlacedId" => $placeOrder, "message"=>$msg]);
                    } else {
                        return response()->json(["status" => true, 'statusCode' => 202, "message" => "deal not available"]);
                    }
                } else {
                    return response()->json(["status"=>true,"statusCode"=>200, "kolProfile"=> 0, "msg" => "Profile does not exist."]);
                }

            } else {
                //Not Authorized
                $msg = __("api_string.not_authorized");
                return response()->json(["status" => false, 'statusCode' => 401, "message" => $msg]);
            }
        } catch (\Throwable $th) {
            $msg = __("api_string.error");
            return response()->json(["statusCode" => 500, "status" => false, "message" => $th->getMessage()]);
        }
    }

    public function getOrderSummary(Request $request){
        try {
            $roleId = auth()->user()->role_id;
            $endUserId = auth()->user()->id;
            if($roleId == 3){
                $OrderSummary = $this->userService->getOrderSummary($request['id'],$endUserId);
                return response()->json(["status"=>true,"statusCode"=>200,"orderSummary"=>$OrderSummary]);
            } else {
                //Not Authorized
                $msg = __("api_string.not_authorized");
                return response()->json(["status" => false, 'statusCode' => 401, "message" => $msg]);
            }

        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function getUserOrderHistory(Request $request){
        try {
            $roleId = auth()->user()->role_id;
            $endUserId = auth()->user()->id;
            if($roleId == 3){
                $OrderSummary = $this->userService->getUserOrderHistory($endUserId);
                return response()->json(["status"=>true,"statusCode"=>200,"orderSummary"=>$OrderSummary]);
            } else {
                //Not Authorized
                $msg = __("api_string.not_authorized");
                return response()->json(["status" => false, 'statusCode' => 401, "message" => $msg]);
            }

        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false,"message"=>$th->getMessage()]);
        }
    }

    public function getKolOrderHistory(Request $request){
        try {
            $roleId = auth()->user()->role_id;
            $kolUserId = auth()->user()->id;
            if($roleId == 2){
                $kolProfile = $this->userService->checkKolProfileExistOrNot($kolUserId);
                if($kolProfile){
                    $OrderSummary = $this->userService->getKolOrderHistory($kolProfile['id']);
                    return response()->json(["status"=>true,"statusCode"=>200,"orderSummary"=>$OrderSummary]);
                } else {
                    return response()->json(["status"=>true,'statusCode'=>201,"message"=>"Please add profile details first."]);
                }
                
            } else {
                //Not Authorized
                $msg = __("api_string.not_authorized");
                return response()->json(["status" => false, 'statusCode' => 401, "message" => $msg]);
            }

        } catch (\Throwable $th) {
            $msg= __("api_string.error");
            return response()->json(["statusCode"=>500,"status"=>false]);
        }
    }
}
