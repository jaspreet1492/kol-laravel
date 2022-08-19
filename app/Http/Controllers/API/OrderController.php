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
                dd($checkKolProfile);
                if ($checkKolProfile) {
                    $checkDeal = $this->userService->checkDealExistOrNot($request['id'], $checkKolProfile['id']);
                    dd($checkDeal);
                    if ($checkDeal) {
                        $placeOrder = $this->userService->placeOrder($request,$end_user_id);
                    } else {
                        return response()->json(["status" => true, 'statusCode' => 202, "message" => "deal not available"]);
                    }
                } else {
                    dd('not available');
                }
                dd('sg');
                $checkDeal = $this->userService->DealCount($profileId);

                if ($request['id']) {
                    // update deal
                    $UpdateDeal = $this->userService->UpdateDeal($request, $KolProfile);
                    $msg = __("api_string.deal_updated");
                    return response()->json(["status" => true, 'statusCode' => 202, "message" => $msg]);
                } else {
                    //add deal
                    if ($checkDealCount < 5) {
                        $addDeal = $this->userService->AddDeal($request, $KolProfile);
                        $msg = __("api_string.deal_added");
                        return response()->json(["status" => true, 'statusCode' => 201, "message" => $msg]);
                    } else {
                        return response()->json(["status" => false, 'statusCode' => 403, "message" => "You cannot create more than 5 Deals."]);
                    }
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
}
