<?php

namespace  App\Http\Services;

use App\Models\User;
use App\Models\UserTokens;
use App\Models\UserAddress;
use App\Models\Address;
use App\Models\KolProfile;
use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\Announcement;
use App\Models\SocialMedia;
use App\Models\Feedback;
use App\Models\Banner;
use App\Models\Order;
use App\Models\Faq;
use App\Models\InformativeVideo;
use App\Http\Controllers\MailController;
use App\Models\KolType;
use App\Models\Deal;
use App\Models\DealRequest;
use App\Models\Bookmark;
use App\Models\ContactUs;
use Illuminate\Support\Facades\Auth;
use Session;
use Crypt;
use Illuminate\Http\Request;
use Validator;
use Mail;
use JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserService
{

    public function getAllUser()
    {
        $allUserData = User::all();
        return $allUserData;
    }

    public function saveChat($request, $userId)
    {
        // dd($request['receiver_id']);
        $checkUser = User::where('id', $request['receiver_id'])->first();
        if($checkUser){
            $checkChat = Chat::where(function($query) use($userId,$request){
                $query->where(function($q) use($userId, $request){
                    $q->where('sender_id',$request['receiver_id'])
                    ->where('receiver_id',$userId);
                })
                ->orWhere(function($q) use($userId, $request){
                    $q->where('sender_id',$userId)
                    ->where('receiver_id',$request['receiver_id']);
                });
            })
            ->first();
    
            if(($checkChat==null)){
                $chatData = new Chat();
                $chatData->sender_id = $userId;
                $chatData->receiver_id = $request['receiver_id'];
                $chatData->message = $request['message'];
                $chat = $chatData->save();
                $lastChatId = $chatData->id;
                if($chatData){
                    $chatThreadData = new ChatThread();
                $chatThreadData->sender_id = $userId;
                $chatThreadData->chat_id = $lastChatId;
                $chatThreadData->receiver_id = $request['receiver_id'];
                $chatThreadData->message = $request['message'];
                $chatThread = $chatThreadData->save();
                return $chatThread;
                }else{
                    return 0;
                }
                
               
            }else{
    
                // dd($checkChat->id);
                $result = Chat::where(['id'=> $checkChat->id])->update(['receiver_id'=>$request['receiver_id'],'sender_id'=>$userId,'message'=>$request['message']]);
                if($result){
                $chatThreadData = new ChatThread();
                $chatThreadData->sender_id = $userId;
                $chatThreadData->chat_id = $checkChat->id;
                $chatThreadData->receiver_id = $request['receiver_id'];
                $chatThreadData->message = $request['message'];
                $chatThread = $chatThreadData->save();
                return $chatThread;
                }else{
                    return 0;
                }
              
            }
        }else{
            return 0;
        }
      
        
        
    }

    public function getChat($request, $userId)
    {
        
    $chatData = ChatThread::where(['status'=> 1])
    ->whereIn('sender_id',[$userId,$request['receiver_id']])
    ->whereIn('receiver_id',[$userId,$request['receiver_id']])
    ->with('getReceiver','kolProfile')
    ->orderBy('id', 'ASC')->get();
    $data =[];
    if(!empty($chatData)){
        foreach($chatData as $chatDatas){
            $obj = [];
                // $obj['userData'] = $chatDatas->getUser;
                $obj['name']=$chatDatas->getSender->name;
                $obj['last_name']=$chatDatas->getSender->last_name;
                // $obj['userData']['role_id']=$chatDatas->getReceiver->role_id;
                $obj['avatar']=$chatDatas->getSender->avatar;
                // $obj['avatar']=(isset($chatDatas->kolProfile->avatar)&& $chatDatas->kolProfile->avatar!=NULL)?$chatDatas->kolProfile->avatar:$chatDatas->getSender->avatar;
                // $obj['userData']['email']=$chatDatas->getReceiver->email;
                $obj['message_id'] = $chatDatas->id;
                $obj['sender_id'] = $chatDatas->sender_id;
                $obj['receiver_id'] = $chatDatas->receiver_id;
                $obj['message'] = $chatDatas->message;
                $obj['sent_at'] = $chatDatas->created_at;
                $obj['edit_at'] = $chatDatas->updated_at;
            
            $data[] = $obj;
        }
    }
   
    return $data;
    }

    public function getChatUsers($request, $userId)
    {   
        
    $chatData = Chat::where(['status'=> 1])
    ->where(function($query) use($userId){
        $query->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId);
    })
    ->with('getSender','getReceiver','kolProfile')
    // ->groupBy('receiver_id')
    ->orderBy('updated_at', 'DESC')->get();
    // dd($chatData);
    $data =[];
    foreach($chatData as $chatDatas){
        $obj = [];
            $obj['name']= ($chatDatas->receiver_id==$userId)?$chatDatas->getSender->name:$chatDatas->getReceiver->name;
            $obj['last_name']=$chatDatas->getSender->last_name;
            $obj['avatar']=(isset($chatDatas->kolProfile->avatar)&& $chatDatas->kolProfile->avatar!=NULL)?$chatDatas->kolProfile->avatar:$chatDatas->getSender->avatar;
            $obj['last_msg'] = $chatDatas->message;
            $obj['time'] = $chatDatas->created_at;
            $obj['profile_id'] = ($chatDatas->receiver_id==$userId)?$chatDatas->sender_id:$chatDatas->receiver_id;
        
        $data[] = $obj;
    }

    return $data;
    } 

    public function deleteMsg($request, $userId)
    {
        $result = ChatThread::where(['id'=> $request['msg_id'],'sender_id'=>$userId,'status'=>1])->update(['status' => 0]);
        return $result;
    }
    
    public function updateViewCount($request, $userId,$views)
    {
        $result = KolProfile::where(['id'=> $request['profile_id']])->update(['total_viewer' => $views+1]);
        return $result;
    }

    public function editMsg($request, $userId)
    {
        $result = ChatThread::where(['id'=> $request['msg_id'],'sender_id'=>$userId])->update(['message' => $request['message']]);
        return $result;
    }
    public function createUser($request, $otp, $roleId)
    {

        // Create new user
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = Hash::make($request->password);
        $user->otp = $otp;
        $user->role_id = $roleId;
        $user->firebase_token = ($request['firebase_token']) ? $request['firebase_token'] : NULL;
        $checkUserSave = $user->save();
        $lastUserId = $user->id;
        if ($checkUserSave) {
            //Generate jwt token
            $token = '';
            if ($otp == null) {
                $customClaims = ['name' => $request['name'], 'email' => $request['email'], 'role_id' => $roleId, 'firebase_token' => $request['firebase_token']];
                $input = $request->only('email', 'password');
                $token = JWTAuth::claims($customClaims)->attempt($input);
            } else {
                $input = $request->only('email', 'password');
                $token = JWTAuth::attempt($input);
                Mail::to($request->email)->send(new \App\Mail\VerifyMail(["url" => $otp]));
            }
            // // Store token in user tokens table
            $saveToken = new UserTokens();
            $saveToken->user_id = $lastUserId;
            $saveToken->otp = $otp;
            $saveToken->token = $token;
            $saveToken->save();
            return $token;
        }
    }

    public function generateJwtToken($checkEmail)
    {
        $customClaims = ['name' => $checkEmail['name'], 'email' => $checkEmail['email'], 'role_id' => $checkEmail['role_id'], 'firebase_token' => $checkEmail['firebase_token']];
        $input = [];
        $input['email'] = $checkEmail['email'];
        $input['password'] = null;
        // $input = $request->only('email', 'password');
        $token = JWTAuth::claims($customClaims)->attempt($input);
        if ($token) {
            $saveToken = new UserTokens();
            $saveToken->user_id = $checkEmail['id'];
            $saveToken->token = $token;
            $saveToken->save();
            return $token;
        } else {
            return false;
        }
    }

    public function updateRoleByUserEmail($request)
    {
        return User::where('email', $request['email'])->update(['role_id' => $request['role_id']]);
    }
    public function checkEmail($email)
    {
        return User::where('email', $email)->first();
    }

    public function sendVerificationCode($userId, $oldOtp, $request, $otp)
    {

        $input = $request->only('email', 'password');
        $token = JWTAuth::attempt($input);
        $updateUserOtp = User::where('id', $userId)->update(['otp' => $otp]);
        if ($updateUserOtp) {
            $updateUserToken = UserTokens::where('user_id', $userId)->where('otp', $oldOtp)->update(['token' => $token, 'otp' => $otp]);
            Mail::to($request->email)->send(new \App\Mail\VerifyMail(["url" => $otp]));
            return $updateUserOtp;
        }
    }

    public function checkOtp($request)
    {
        return User::where('otp', $request['otp'])->where('email', $request['email'])->first();
    }

    public function makeUserVerifiy($otp)
    {
        return User::where('otp', $otp)->update(['is_varified' => 1]);
    }

    public function verifyEmailOtp($otp, $userId)
    {

        return UserTokens::select('token')->where('user_id', $userId)->where('otp', $otp)->first();
    }

    public function insertNewOtp($otp, $userId, $oldOtp, $email)
    {
        $check = UserTokens::where('otp', $oldOtp)->first();
        if ($check) {
            $updateResponse = User::where('id', $userId)->update(['otp' => $otp]);
            if ($updateResponse) {
                UserTokens::where('token', $check['token'])->update(['otp' => $otp]);
                Mail::to($email)->send(new \App\Mail\VerifyMail(["url" => $otp]));
                return $updateResponse;
            }
        }
    }

    public function checkPassword($password)
    {
        return User::where('password', Hash::make($password));
    }

    public function updatePassword($request, $userId, $email)
    {
        $updatePass = User::where('email', $email)->update(['password' => Hash::make($request['new_password'])]);
        if ($updatePass) {
            $input = [];
            $input['email'] = $email;
            $input['password'] = $request['new_password'];
            $token = JWTAuth::attempt($input);
            $saveToken = new UserTokens();
            $saveToken->user_id = $userId;
            $saveToken->token = $token;
            $saveToken->save();
            return $token;
        } else {
            return false;
        }
    }

    public function getUserById($userId)
    {
        return User::where('id', $userId)->with('getAddress')->first();
    }

    public function userLogin($request, $oldOtp, $userId)
    {
        if (!Hash::check($request['password'], $oldOtp)) {
            return false;
        } else {
            $input = $request->only('email', 'password');
            $token = JWTAuth::attempt($input);
            $saveToken = new UserTokens();
            $saveToken->user_id = $userId;
            $saveToken->token = $token;
            $saved = $saveToken->save();
            return $token;
        }
    }

    public function forgetPassword($request)
    {
        $checkEmail = User::select('email', 'id', 'is_varified')->where('email', $request['email'])->first();
        if ($checkEmail) {
            if ($checkEmail['is_varified'] == 1) {
                $otp = rand(100000, 999999);
                User::where('id', $checkEmail['id'])->update(['password_reset_code' => $otp]);
                $user = ['url' => $otp];
                $Email = Mail::to($request->email)->send(new \App\Mail\VerifyMail($user));
                return "true";
            } else {
                return false;
            }
        } else {
            return;
        }
    }

    public function changePassword($request)
    {

        $valdiation = Validator::make($request->all(), [

            'password' => 'required|min:6',
            'c_password' => 'required|same:password',
        ]);

        if ($valdiation->fails()) {
            return response()->json($valdiation->errors(), 202);
        }
        User::where('id', $request->user_id)->update(['password' => Hash::make($request->password), 'password_reset_code' => NULL]);

        return response()->json(['message' => 'password updated successfully'], 200);
    }

    public function checkLogOut($request)
    {
        $header = $request->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            $token = Str::substr($header, 7);
        }
        if ($token) {
            $expiredToken = UserTokens::select('token')->where('token', $token)->first();
            if ($expiredToken) {
                UserTokens::where('token', $token)->delete();
                return response()->json(['success' => true, 'statusCode' => 200, 'message' => 'User logged out successfully']);
            } else {
                return response()->json(['success' => false, 'statusCode' => true, 'message' => 'Sorry, the user cannot be logged out']);
            }
        }
    }
    public function resetPassword($request)
    {
        $header = $request->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            $token = Str::substr($header, 7);
        }
        if ($token) {
            $valdiation = Validator::make(
                $request->all(),
                [
                    'current_password' => 'required|min:6',
                    'new_password' => 'required|min:6',
                    'confirm_new_password' => 'required|same:new_password',
                ]
            );
            if ($valdiation->fails()) {
                return response()->json($valdiation->errors(), 202);
            }
            User::select('password')->where('id', $request->user_id)->update(['password' => Hash::make($request->current_password)]);
            $currentUser = Auth::user();
        }
    }


    // KOL Profile Logic Here
    public function AddKolProfile($request, $userId)
    {

        $profileImgUrl = ($request['avatar']) ? KolProfile::makeImageUrl($request['avatar']) : NULL;
        $bannerImgUrl = ($request['banner']) ? KolProfile::makeImageUrl($request['banner']) : NULL;
        $kolProfileData = new KolProfile();
        $kolProfileData->user_id = $userId;
        $kolProfileData->languages = implode(',', $request['languages']);
        $kolProfileData->bio = $request['bio'];
        $kolProfileData->personal_email = $request['personal_email'];
        $kolProfileData->kol_type = $request['kol_type'];
        $kolProfileData->state = $request['state'];
        $kolProfileData->zip_code = $request['zip_code'];
        $kolProfileData->city = $request['city'];
        $kolProfileData->social_active = $request['social_active'];
        $kolProfileData->video_links = implode(',', $request['video_links']);
        $kolProfileData->tags = implode(',', $request['tags']);
        $kolProfileData->avatar = $profileImgUrl;
        $kolProfileData->banner = $bannerImgUrl;
        $kolProfileData->status = 1;
        $checkUserSaved = $kolProfileData->save();
        $lastProfileId = $kolProfileData->id;
        
        if ($lastProfileId) {
            foreach ($request['social_media'] as $requestMediaData) {
                $socialMediaName = $requestMediaData['name'];

                switch ($socialMediaName) {
                    case "instagram":
                        $socialMediaIcon = "fa fa-instagram";
                      break;
                    case "youtube":
                        $socialMediaIcon = "fa fa-youtube";
                      break;
                    case "tik-tok":
                        $socialMediaIcon = "bi bi-tiktok";
                      break;
                    case "facebook":
                        $socialMediaIcon = "fa fa-facebook";
                      break;
                    case "snapchat":
                        $socialMediaIcon = "fa fa-snapchat-ghost";
                      break;
                    default:
                        $socialMediaIcon = "";
                  }
                $kolSocialData = new SocialMedia();
                $kolSocialData->user_id = $userId;
                $kolSocialData->profile_id = $lastProfileId;
                $kolSocialData->name = $requestMediaData['name'];
                $kolSocialData->social_icon = $socialMediaIcon;
                $kolSocialData->social_user_id = $requestMediaData['social_user_id'];
                $kolSocialData->followers = $requestMediaData['followers'];
                $kolSocialMedia = $kolSocialData->save();
            }
        }
        return $lastProfileId;
    }

    // Add Announcement
    public function AddAnnouncement($request, $userId, $profile_id)
    {
        $AnnouncementImg = ($request['image']) ? Announcement::makeImageUrl($request['image']) : NULL;
        $AnnouncementData = new Announcement();
        $AnnouncementData->user_id = $userId;
        $AnnouncementData->profile_id = $profile_id;
        $AnnouncementData->title = $request['title'];
        $AnnouncementData->description = $request['description'];
        $AnnouncementData->start_date = $request['start_date'];
        $AnnouncementData->end_date = $request['end_date'];
        $AnnouncementData->social_platform = $request['social_platform'];
        $AnnouncementData->image = $AnnouncementImg;
        $AnnouncementDataSaved = $AnnouncementData->save();
        $lastAnnouncementId = $AnnouncementData->id;

        return $lastAnnouncementId;
    }

    // Place Order
    public function placeOrder($request, $userId, $dealData)
    {
        $OrderData = new Order();
        $OrderData->deal_id = $request['deal_id'];
        $OrderData->order_id = rand(10000000,99999999);
        $OrderData->kol_profile_id = $request['kol_profile_id'];
        $OrderData->end_user_id = $userId;
        $OrderData->start_date = $request['start_date'];
        $OrderData->end_date = date('Y-m-d H:i:s',strtotime($dealData['total_days'].' days',strtotime(str_replace('/', '-', $request['start_date']))));
        // $tax = [array(
        //     "tax_name" => "gst",
        //     "tax_percentage" => "18",
        //     )]; 
        // $OrderData->tax = json_encode($tax);
        $OrderData->tax = '';
        $order_summary = [
            "deal_id" => $request['deal_id'],
            "kol_profile_id" => $request['kol_profile_id'],
            "deal_title" => $dealData['title'],
            "description" => $dealData['description'],
            "type" => $dealData['type'],
            "total_days" => $dealData['total_days'],
            "end_user_id" => $userId,
            "tax_percentage" => "0",
            "currency" => "INR",
            "price" => $dealData['price']
            ];
        $OrderData->order_summary = json_encode($order_summary);
        $OrderDataSaved = $OrderData->save();
        $lastOrderId = $OrderData->id;

        return $lastOrderId;
    }

    // Request Deal
    // public function requestDeal($request, $userId)
    // {
    //     $DealRequest = new DealRequest();
    //     $DealRequest->end_user_id = $userId;
    //     $DealRequest->kol_user_id = $request['kol_profile_id'];
    //     $DealDataSaved = $DealRequest->save();
    //     $lastDealId = $DealRequest->id;

    //     return $lastDealId;
    // }

    // Request Deal
    // public function watchDeal($request, $kolUserId)
    // {
    //     $updateResponse = DealRequest::where('id', $request['id'])->where('kol_user_id',$kolUserId)->where('end_user_id',$request['end_user_id'])->update(['status' => 0]);

    //     return $updateResponse;
    // }

    // Add Deal
    public function AddDeal($request, $KolProfile)
    {
        $DealData = new Deal();
        $DealData->kol_profile_id = $KolProfile['id'];
        $DealData->title = $request['title'];
        $DealData->description = $request['description'];
        $DealData->type = $request['type'];
        $DealData->total_days = $request['total_days'];
        $DealData->price = $request['price'];
        $DealDataSaved = $DealData->save();
        $lastDealId = $DealData->id;

        return $lastDealId;
    }

    // Add KolType
    public function AddKolType($request)
    {
        $KolTypeData = new KolType();
        $KolTypeData->name = $request['name'];
        $KolTypeDataSaved = $KolTypeData->save();
        $lastKolTypeId = $KolTypeData->id;

        return $lastKolTypeId;
    }


    // Add Bookmark
    public function AddBookmark($kol_profile_id,$endUserId,$kol_user_id)
    {
        $BookmarkData = new Bookmark();
        $BookmarkData->end_user_id = $endUserId;
        $BookmarkData->kol_profile_id = $kol_profile_id;
        $BookmarkData->kol_user_id = $kol_user_id;
        $BookmarkDataSaved = $BookmarkData->save();
        $lastBookmarkId = $BookmarkData->id;

        return $lastBookmarkId;
    }


    // Add Feedback
    public function AddFeedback($request,$endUserId,$kol_user_id)
    {
        $FeedbackData = new Feedback();
        $FeedbackData->end_user_id = $endUserId;
        $FeedbackData->kol_profile_id = $request['kol_profile_id'];
        $FeedbackData->kol_user_id = $kol_user_id;
        $FeedbackData->comment = $request['comment'];
        $FeedbackData->rating = ($request['rating']) ? $request['rating'] : 1;
        $FeedbackDataSaved = $FeedbackData->save();
        $lastFeedbackId = $FeedbackData->id;

        return $lastFeedbackId;
    }

    // Add Banner
    public function AddBanner($request,$endUserId)
    {
        $BannerData = new Banner();
        $BannerData->user_id = $endUserId;
        $BannerData->title = $request['title'];
        $BannerData->description = $request['description'];
        $BannerData->banner = Banner::makeImageUrl($request['banner']);
        $BannerDataSaved = $BannerData->save();
        $lastBannerId = $BannerData->id;

        return $lastBannerId;
    }

    // Add Faq
    public function AddFaq($request)
    {
        $FaqData = new Faq();
        $FaqData->question = $request['question'];
        $FaqData->answer = $request['answer'];
        $FaqDataSaved = $FaqData->save();
        $lastFaqId = $FaqData->id;

        return $lastFaqId;
    }

    // Add Information
    public function AddInformativeVideo($request)
    {
        $InformativeVideoData = new InformativeVideo();
        $InformativeVideoData->title = $request['title'];
        $InformativeVideoData->description = $request['description'];
        $InformativeVideoData->banner = $request['banner'];
        $InformativeVideoSaved = $InformativeVideoData->save();
        $lastIvId = $InformativeVideoData->id;

        return $lastIvId;
    }

    // Add User Address
    public function AddAddress($request,$userId)
    {
        $AddressData = new Address();
        $AddressData->user_id = $userId;
        $AddressData->address = $request['address'];
        $AddressData->landmark = $request['landmark'];
        $AddressData->city = $request['city'];
        $AddressData->state = $request['state'];
        $AddressData->zip = $request['zip'];
        $AddressData->country = $request['country'];
        $AddressSaved = $AddressData->save();
        $lastAddressId = $AddressData->id;

        return $lastAddressId;
    }

    // View KolType
    public function ViewKolType($id)
    {
        $KolTypeData = KolType::where('id', $id)->where('status',1)->first();

        return $KolTypeData;
    }

    // Update KolType
    public function UpdateKolType($request,$id)
    {
        $updateResponse = KolType::where('id', $id)->update(['name' => $request['name']]);

        return $updateResponse;
    }

    // get KolType
    public function KolTypeList($request)
    {
        $updateResponse = KolType::where('status', 1)->pluck('name','id')->toArray();
        $keys = array_keys($updateResponse);
        $values = array_values($updateResponse);
        $response = array_combine($keys,$values);
        
        return $response;
    }

    // get TotalUsers
    public function getTotalUsers()
    {
        $response = User::where('status', 1)->where('role_id',3)->count();
        
        return $response;
    }

    // get TotalUsers
    public function getTotalkol()
    {
        $response = User::where('status', 1)->where('role_id',2)->count();
        
        return $response;
    }

    // get OrderSummary
    public function getOrderSummary($id,$endUserId)
    {
        $response = Order::where('id', $id)->where('end_user_id',$endUserId)->get();
        $orderSummary = [];
        foreach($response as $order_summary){
            $orderSummary['id'] = $order_summary['id'];
            $orderSummary['deal_id'] = $order_summary['deal_id'];
            $orderSummary['order_id'] = $order_summary['order_id'];
            $orderSummary['kol_profile_id'] = $order_summary['kol_profile_id'];
            $orderSummary['end_user_id'] = $order_summary['end_user_id'];
            $orderSummary['start_date'] = $order_summary['start_date'];
            $orderSummary['end_date'] = $order_summary['end_date'];
            $orderSummary['order_summary'] = json_decode($order_summary['order_summary']);
        }
        return $orderSummary;
    }

    // get User Order History
    public function getUserOrderHistory($endUserId)
    {
        $response = Order::where('end_user_id', $endUserId)->get();
        $orderSummary = [];
        $i=0;
        foreach($response as $order_summary){
            $orderSummary[$i]['id'] = $order_summary['id'];
            $orderSummary[$i]['deal_id'] = $order_summary['deal_id'];
            $orderSummary[$i]['order_id'] = $order_summary['order_id'];
            $orderSummary[$i]['kol_profile_id'] = $order_summary['kol_profile_id'];
            $orderSummary[$i]['end_user_id'] = $order_summary['end_user_id'];
            $orderSummary[$i]['start_date'] = $order_summary['start_date'];
            $orderSummary[$i]['end_date'] = $order_summary['end_date'];
            $orderSummary[$i]['order_summary'] = json_decode($order_summary['order_summary']);
            $i++;
        }
        return $orderSummary;
    }

    // get User Order History
    public function getKolOrderHistory($kolProfileId)
    {
        $response = Order::where('kol_profile_id', $kolProfileId)->get();
        $orderSummary = [];
        $i=0;
        foreach($response as $order_summary){
            $orderSummary[$i]['id'] = $order_summary['id'];
            $orderSummary[$i]['deal_id'] = $order_summary['deal_id'];
            $orderSummary[$i]['order_id'] = $order_summary['order_id'];
            $orderSummary[$i]['kol_profile_id'] = $order_summary['kol_profile_id'];
            $orderSummary[$i]['end_user_id'] = $order_summary['end_user_id'];
            $orderSummary[$i]['start_date'] = $order_summary['start_date'];
            $orderSummary[$i]['end_date'] = $order_summary['end_date'];
            $orderSummary[$i]['order_summary'] = json_decode($order_summary['order_summary']);
            $i++;
        }
        return $orderSummary;
    }

    // get TotalUsers
    public function getTotalVideos()
    {
        $profileDetails = kolProfile::where('status', 1)->whereHas('getUser', function($query) {
            $query->where('role_id', '=', 2); // '=' is optional
        })->get();
        $response= [];
        foreach($profileDetails as $kolProfile){
            $video_links = explode(',', $kolProfile['video_links']);
            $count = count($video_links);
            array_push($response,$count);
        }
        $total_sum = array_sum($response);

        return $total_sum;
    }

    // get Kol Users Feedback
    public function getKolFeedbackList($kolProfileId)
    {
        $FeedbackLists = Feedback::where('kol_profile_id', $kolProfileId)->with('getKolProfile')->with('getUser')->get();

        $listFeedbacks = [];
        $i = 0;
        foreach($FeedbackLists as $key => $FeedbackList){
            
            $listFeedbacks[$i]['feedback_id'] = $FeedbackList['id'];
            $listFeedbacks[$i]['end_user_id'] = $FeedbackList['end_user_id'];
            $listFeedbacks[$i]['kol_user_id'] = $FeedbackList['kol_user_id'];
            $listFeedbacks[$i]['kol_profile_id'] = $FeedbackList['kol_profile_id'];
            $listFeedbacks[$i]['comment'] = $FeedbackList['comment'];
            $listFeedbacks[$i]['rating'] = $FeedbackList['rating'];
            $listFeedbacks[$i]['profile_id'] = $FeedbackList['getKolProfile']['id'];
            $listFeedbacks[$i]['languages'] = $FeedbackList['getKolProfile']['languages'];
            $listFeedbacks[$i]['bio'] = $FeedbackList['getKolProfile']['bio'];
            $listFeedbacks[$i]['avatar'] = $FeedbackList['getKolProfile']['avatar'];
            $listFeedbacks[$i]['personal_email'] = $FeedbackList['getKolProfile']['personal_email'];
            $listFeedbacks[$i]['kol_type'] = $FeedbackList['getKolProfile']['kol_type'];
            $listFeedbacks[$i]['state'] = $FeedbackList['getKolProfile']['state'];
            $listFeedbacks[$i]['city'] = $FeedbackList['getKolProfile']['city'];
            $listFeedbacks[$i]['zip_code'] = $FeedbackList['getKolProfile']['zip_code'];
            $listFeedbacks[$i]['total_viewer'] = $FeedbackList['getKolProfile']['total_viewer'];
            $listFeedbacks[$i]['banner'] = $FeedbackList['getKolProfile']['banner'];
            $listFeedbacks[$i]['social_active'] = $FeedbackList['getKolProfile']['social_active'];
            $listFeedbacks[$i]['video_links'] = $FeedbackList['getKolProfile']['video_links'];
            $listFeedbacks[$i]['tags'] = $FeedbackList['getKolProfile']['tags'];
            $listFeedbacks[$i]['user_id'] = $FeedbackList['getUser']['id'];
            $listFeedbacks[$i]['username'] = $FeedbackList['getUser']['name'];
            $listFeedbacks[$i]['email'] = $FeedbackList['getUser']['email'];
            $listFeedbacks[$i]['role_id'] = $FeedbackList['getUser']['role_id'];
            $listFeedbacks[$i]['profile_image'] = $FeedbackList['getUser']['avatar'];
            $listFeedbacks[$i]['gender'] = $FeedbackList['getUser']['gender'];
            $listFeedbacks[$i]['phone'] = $FeedbackList['getUser']['phone'];

            $i++;
        }
        
        return $listFeedbacks;
    }

    public function contactUs($request)
    {
        $contactUsData = new ContactUs();
        $contactUsData->first_name = $request['first_name'];
        $contactUsData->last_name = $request['last_name'];
        $contactUsData->email = $request['email'];
        $contactUsData->mobile = $request['mobile'];
        $contactUsData->messsage = $request['messsage'];
        $contactUsDataSaved = $contactUsData->save();
        $lastContactId = $contactUsData->id;
       
        $Email = Mail::to("jaspreetkaur@bootesnull.com")->send(new \App\Mail\VerifyMail(["url" => $contactUsData->messsage]));
        
        return $lastContactId;

    }

    // get End Users Feedback
    public function getEndUserFeedbackList($userId)
    {
        $FeedbackLists = Feedback::where('end_user_id', $userId)->get();

        $listFeedbacks = [];
        $i = 0;
        foreach($FeedbackLists as $key => $FeedbackList){
            
            $listFeedbacks[$i]['feedback_id'] = $FeedbackList['id'];
            $listFeedbacks[$i]['end_user_id'] = $FeedbackList['end_user_id'];
            $listFeedbacks[$i]['kol_user_id'] = $FeedbackList['kol_user_id'];
            $listFeedbacks[$i]['kol_profile_id'] = $FeedbackList['kol_profile_id'];
            $listFeedbacks[$i]['comment'] = $FeedbackList['comment'];
            $listFeedbacks[$i]['rating'] = $FeedbackList['rating'];
            $listFeedbacks[$i]['profile_id'] = $FeedbackList['getKolProfile']['id'];
            $listFeedbacks[$i]['languages'] = $FeedbackList['getKolProfile']['languages'];
            $listFeedbacks[$i]['bio'] = $FeedbackList['getKolProfile']['bio'];
            $listFeedbacks[$i]['avatar'] = $FeedbackList['getKolProfile']['avatar'];
            $listFeedbacks[$i]['personal_email'] = $FeedbackList['getKolProfile']['personal_email'];
            $listFeedbacks[$i]['kol_type'] = $FeedbackList['getKolProfile']['kol_type'];
            $listFeedbacks[$i]['state'] = $FeedbackList['getKolProfile']['state'];
            $listFeedbacks[$i]['city'] = $FeedbackList['getKolProfile']['city'];
            $listFeedbacks[$i]['zip_code'] = $FeedbackList['getKolProfile']['zip_code'];
            $listFeedbacks[$i]['total_viewer'] = $FeedbackList['getKolProfile']['total_viewer'];
            $listFeedbacks[$i]['banner'] = $FeedbackList['getKolProfile']['banner'];
            $listFeedbacks[$i]['social_active'] = $FeedbackList['getKolProfile']['social_active'];
            $listFeedbacks[$i]['video_links'] = $FeedbackList['getKolProfile']['video_links'];
            $listFeedbacks[$i]['tags'] = $FeedbackList['getKolProfile']['tags'];
            $listFeedbacks[$i]['user_id'] = $FeedbackList['getUser']['id'];
            $listFeedbacks[$i]['username'] = $FeedbackList['getUser']['name'];
            $listFeedbacks[$i]['email'] = $FeedbackList['getUser']['email'];
            $listFeedbacks[$i]['role_id'] = $FeedbackList['getUser']['role_id'];
            $listFeedbacks[$i]['profile_image'] = $FeedbackList['getUser']['avatar'];
            $listFeedbacks[$i]['gender'] = $FeedbackList['getUser']['gender'];
            $listFeedbacks[$i]['phone'] = $FeedbackList['getUser']['phone'];

            $i++;
        }
        
        return $listFeedbacks;
    }

    // Update Announcement
    public function UpdateAnnouncement($request, $id)
    {
        $id = ($request['id']) ? $request['id'] : NULL;
        $AnnouncementImg = "";
        if($request['image']){
            $AnnouncementImg = ($request['image']) ? Announcement::makeImageUrl($request['image']) : NULL;
        }
        $updateData = [];

        if ($AnnouncementImg) {
            
            $updateData = [
                'title' => $request['title'],
                'description' => $request['description'],
                'start_date' => $request['start_date'],
                'end_date' => $request['end_date'],
                'social_platform' => $request['social_platform'],
                'image' => $AnnouncementImg,
            ];

        } else {
            $updateData = [
                'title' => $request['title'],
                'description' => $request['description'],
                'start_date' => $request['start_date'],
                'end_date' => $request['end_date'],
                'social_platform' => $request['social_platform'],
            ];
        }

        $updateResponse = Announcement::where('id', $id)->update($updateData);

        return $updateResponse;
    }

    // Update UserImage
    public function storeUserImage($request, $userId)
    {

        $UserImg = ($request['avatar']) ? User::makeImageUrl($request['avatar']) : NULL;
        $updateData = [];

        $updateData = [
            'avatar' => $UserImg,
        ];

        $updateResponse = User::where('id', $userId)->update($updateData);
        
        return $updateResponse;
    }

    // Update Banner
    public function UpdateBanner($request, $id)
    {
        $id = ($request['id']) ? $request['id'] : NULL;
        $Banner = ($request['banner']) ? Banner::makeImageUrl($request['banner']) : NULL;
        $updateData = [];

        if ($Banner) {
            
            $updateData = [
                'title' => $request['title'],
                'description' => $request['description'],
                'banner' => $Banner,
            ];

        } else {
            $updateData = [
                'title' => $request['title'],
                'description' => $request['description'],
            ];
        }

        $updateResponse = Banner::where('id', $id)->update($updateData);

        return $updateResponse;
    }


    // Update Faq
    public function UpdateFaq($request, $id)
    {
        $id = ($request['id']) ? $request['id'] : NULL;
        $updateData = [];
            
        $updateData = [
            'question' => $request['question'],
            'answer' => $request['answer'],
        ];
  
        $updateResponse = Faq::where('id', $id)->update($updateData);

        return $updateResponse;
    }


    // Update Deal
    public function UpdateDeal($request, $KolProfile)
    {
        $id = ($request['id']) ? $request['id'] : NULL;
        $updateData = [];
            
        $updateData = [
            'title' => $request['title'],
            'description' => $request['description'],
            'type' => $request['type'],
            'total_days' => $request['total_days'],
            'price' => $request['price'],
        ];
        
        $updateResponse = Deal::where('id', $id)->where('kol_profile_id', $KolProfile['id'])->update($updateData);

        return $updateResponse;
    }

    // Update UserDetails
    public function UpdateUserDetails($request, $userId)
    {
        $updateData = [];
            
        $updateData = [
            'name' => $request['name'],
            'last_name' => $request['last_name'],
            'gender' => $request['gender'],
            'phone' => $request['phone']
        ];
  
        $updateResponse = User::where('id', $userId)->update($updateData);
        
        return $updateResponse;
    }

    // Update Address
    public function UpdateAddress($request, $userId)
    {
        $updateData = [];
            
        $updateData = [
            'address' => $request['address'],
            'landmark' => $request['landmark'],
            'city' => $request['city'],
            'state' => $request['state'],
            'zip' => $request['zip'],
            'country' => $request['country']
        ];
  
        $updateResponse = Address::where('user_id', $userId)->update($updateData);

        return $updateResponse;
    }


    // Update InformativeVideo
    public function UpdateInformativeVideo($request, $id)
    {
        $id = ($request['id']) ? $request['id'] : NULL;
        $updateData = [];

            $updateData = [
                'title' => $request['title'],
                'description' => $request['description'],
                'banner' => $request['banner'],
            ];
       

        $updateResponse = InformativeVideo::where('id', $id)->update($updateData);

        return $updateResponse;
    }

    public function ViewAnnouncementById($id)
    {
        $announcementData = Announcement::where('announcements.id', $id)->with('getUser')->get();

        return $announcementData;
    }

    public function getAnnouncementstatus($id)
    {
        $announcementData = Announcement::select('status')->where('announcements.id', $id)->first();
        return $announcementData['status'];
    }

    public function getKolTypestatus($id)
    {
        $KolTypeData = KolType::select('status')->where('kol_type.id', $id)->first();
        return $KolTypeData['status'];
    }

    public function getAnnouncementListByKolUserId($request, $userId){

        $kolAnnouncementList = [];
        $pageNo = ($request['page']) ? $request['page'] : 1;
        $limit = ($request['limit']) ? $request['limit'] : 10;

        $AnnouncementList = Announcement::where('user_id',$userId)->where('status',1)->with('getUser')->skip(($pageNo - 1) * $limit)->take($limit)->get();
        $AnnouncementListCount = Announcement::where('user_id',$userId)->where('status',1)->with('getUser')->skip(($pageNo - 1) * $limit)->take($limit)->count();

        $kolAnnouncementList['kolAnnouncementList'] = $AnnouncementList;
        $kolAnnouncementList['AnnouncementListCount'] = $AnnouncementListCount;
        
        return $kolAnnouncementList;
    }

    public function getDealsById($id,$kolProfileId){

        $Deal = Deal::where('id',$id)->where('kol_profile_id',$kolProfileId)->where('status',1)->with('getKolProfile')->first();

        return $Deal;
    }

    public function getDealsListByKolProfileId($kolProfileId){

        $Deal = Deal::where('kol_profile_id',$kolProfileId)->where('status',1)->with('getKolProfile')->get();

        return $Deal;
    }

    public function getAnnouncementList($request,$userId){

        $logggedInUserAnnouncementList = [];
        $pageNo = ($request['page']) ? $request['page'] : 1;
        $limit = ($request['limit']) ? $request['limit'] : 10;

        $AnnouncementList = Announcement::where('user_id',$userId)->where('status',1)->with('getUser')->skip(($pageNo - 1) * $limit)->take($limit)->get();
        $AnnouncementListCount = Announcement::where('user_id',$userId)->where('status',1)->with('getUser')->skip(($pageNo - 1) * $limit)->take($limit)->count();

        $logggedInUserAnnouncementList['logggedInUserAnnouncementList'] = $AnnouncementList;
        $logggedInUserAnnouncementList['AnnouncementListCount'] = $AnnouncementListCount;

        return $logggedInUserAnnouncementList;
    }

    public function getBannerList(){

        $BannerList = Banner::where('status',1)->get();

        return $BannerList;
    }

    public function getFaqList(){

        $FaqList = Faq::where('status',1)->get();

        return $FaqList;
    }

    public function getInformativeVideoList(){

        $InformativeVideoList = InformativeVideo::get();

        return $InformativeVideoList;
    }

    public function getBookmarks($userId){

        $BookmarkLists = Bookmark::where('end_user_id',$userId)->where('status',1)->with('getKolProfile')->with('getUser')->get();
        $listBookMarks = [];
        $i = 0;
        foreach($BookmarkLists as $key => $BookmarkList){
            $listBookMarks[$i]['bookmark_id'] = $BookmarkList['id'];
            $listBookMarks[$i]['end_user_id'] = $BookmarkList['end_user_id'];
            $listBookMarks[$i]['kol_user_id'] = $BookmarkList['kol_user_id'];
            $listBookMarks[$i]['kol_profile_id'] = $BookmarkList['kol_profile_id'];
            $listBookMarks[$i]['profile_id'] = $BookmarkList['getKolProfile']['id'];
            $listBookMarks[$i]['languages'] = $BookmarkList['getKolProfile']['languages'];
            $listBookMarks[$i]['bio'] = $BookmarkList['getKolProfile']['bio'];
            $listBookMarks[$i]['avatar'] = $BookmarkList['getKolProfile']['avatar'];
            $listBookMarks[$i]['personal_email'] = $BookmarkList['getKolProfile']['personal_email'];
            $listBookMarks[$i]['kol_type'] = $BookmarkList['getKolProfile']['kol_type'];
            $listBookMarks[$i]['state'] = $BookmarkList['getKolProfile']['state'];
            $listBookMarks[$i]['city'] = $BookmarkList['getKolProfile']['city'];
            $listBookMarks[$i]['zip_code'] = $BookmarkList['getKolProfile']['zip_code'];
            $listBookMarks[$i]['total_viewer'] = $BookmarkList['getKolProfile']['total_viewer'];
            $listBookMarks[$i]['banner'] = $BookmarkList['getKolProfile']['banner'];
            $listBookMarks[$i]['social_active'] = $BookmarkList['getKolProfile']['social_active'];
            $listBookMarks[$i]['video_links'] = $BookmarkList['getKolProfile']['video_links'];
            $listBookMarks[$i]['tags'] = $BookmarkList['getKolProfile']['tags'];
            $listBookMarks[$i]['user_id'] = $BookmarkList['getUser']['id'];
            $listBookMarks[$i]['username'] = $BookmarkList['getUser']['name'];
            $listBookMarks[$i]['email'] = $BookmarkList['getUser']['email'];
            $listBookMarks[$i]['role_id'] = $BookmarkList['getUser']['role_id'];
            $listBookMarks[$i]['profile_image'] = $BookmarkList['getUser']['avatar'];
            $listBookMarks[$i]['gender'] = $BookmarkList['getUser']['gender'];
            $listBookMarks[$i]['phone'] = $BookmarkList['getUser']['phone'];

            $i++;
        }
        
        return $listBookMarks;
    }

    public function getAllAnnouncementList($request){

        $allAnnoucementData = [];
        $pageNo = ($request['page']) ? $request['page'] : 1;
        $limit = ($request['limit']) ? $request['limit'] : 10;

        $AnnouncementList = Announcement::where('status',1)->with('getUser')->skip(($pageNo - 1) * $limit)->take($limit)->get();
        $AnnouncementListCount = Announcement::where('status',1)->with('getUser')->skip(($pageNo - 1) * $limit)->take($limit)->count();

        $allAnnoucementData['AnnouncementList'] = $AnnouncementList;
        $allAnnoucementData['AnnouncementListCount'] = $AnnouncementListCount ;

        return $allAnnoucementData;
    }

    public function deleteAnnouncement($id){

        $Announcement = Announcement::where('id',$id)->delete();

        return $Announcement;
    }

    public function deleteDeal($id,$kolProfileId){

        $Deal = Deal::where('id',$id)->where('kol_profile_id',$kolProfileId)->delete();

        return $Deal;
    }

    public function deleteBanner($id){

        $Banner = Banner::where('id',$id)->delete();
        
        return $Banner;
    }

    public function deleteFaq($id){

        $Faq = Faq::where('id',$id)->delete();
        
        return $Faq;
    }
    
    public function deleteInformativeVideo($id){

        $InformativeVideo = InformativeVideo::where('id',$id)->delete();

        return $InformativeVideo;
    }
    
    public function deleteBookmark($kol_profile_id,$endUserId){

        $Bookmark = Bookmark::where('kol_profile_id', $kol_profile_id)->where('end_user_id', $endUserId)->delete();

        return $Bookmark;
    }

    public function ActiveInactiveKolType($id,$status){

        $kolType = KolType::where('id',$id)->update(['status' => $status]);

        return $kolType;
    }

    public function AnnouncementActiveInactive($id,$status){

        $updateResponse = Announcement::where('id', $id)->update(['status' => $status]);

        return $updateResponse;
    }
    
    public function UpdateKolProfile($request, $userId)
    {

        // $id = ($request['id']) ? $request['id'] : NULL;
        $profileImgUrl = ($request['avatar']) ? KolProfile::makeImageUrl($request['avatar']) : NULL;
        $bannerImgUrl = ($request['banner']) ? KolProfile::makeImageUrl($request['banner']) : NULL;
        $updateData = [];
        if ($profileImgUrl && $bannerImgUrl) {
            $updateData = [
                'languages' => implode(',', $request['languages']),
                'bio' => $request['bio'],
                'personal_email' => $request['personal_email'],
                'kol_type' => $request['kol_type'],
                'state' => $request['state'],
                'zip_code' => $request['zip_code'],
                'city' => $request['city'],
                'total_viewer' => $request['total_viewer'],
                'social_active' => $request['social_active'],
                'video_links' => implode(',', $request['video_links']),
                'tags' => implode(',', $request['tags']),
                'avatar' => $profileImgUrl,
                'banner' => $bannerImgUrl,
            ];

        } elseif ($profileImgUrl) {

            $updateData = [
                'avatar' => $profileImgUrl,
                'languages' => implode(',', $request['languages']),
                'bio' => $request['bio'],
                'personal_email' => $request['personal_email'],
                'kol_type' => $request['kol_type'],
                'state' => $request['state'],
                'zip_code' => $request['zip_code'],
                'city' => $request['city'],
                'total_viewer' => $request['total_viewer'],
                'social_active' => $request['social_active'],
                'video_links' => implode(',', $request['video_links']),
                'tags' => implode(',', $request['tags']),
            ];

        } elseif ($bannerImgUrl) {

            $updateData = [
                'banner' => $bannerImgUrl,
                'languages' => implode(',', $request['languages']),
                'bio' => $request['bio'],
                'personal_email' => $request['personal_email'],
                'kol_type' => $request['kol_type'],
                'state' => $request['state'],
                'zip_code' => $request['zip_code'],
                'city' => $request['city'],
                'total_viewer' => $request['total_viewer'],
                'social_active' => $request['social_active'],
                'video_links' => implode(',', $request['video_links']),
                'tags' => implode(',', $request['tags']),
            ];
        } else {
            $updateData = [
                'languages' => implode(',', $request['languages']),
                'bio' => $request['bio'],
                'personal_email' => $request['personal_email'],
                'kol_type' => $request['kol_type'],
                'state' => $request['state'],
                'zip_code' => $request['zip_code'],
                'city' => $request['city'],
                'total_viewer' => $request['total_viewer'],
                'social_active' => $request['social_active'],
                'video_links' => implode(',', $request['video_links']),
                'tags' => implode(',', $request['tags']),
            ];
        }

        $updateResponse = KolProfile::where('user_id', $userId)->update($updateData);
        $profile_id = KolProfile::where('user_id', $userId)->pluck('id');

        if ($updateResponse) {
            $socialMedia = SocialMedia::where('user_id', $userId)->where('user_id',$userId)->count();

            if($socialMedia>0){
                $socialAccounts = SocialMedia::where('user_id', $userId)->delete();
            }
            foreach ($request['social_media'] as $requestMediaData) {                
                $socialMediaName = $requestMediaData['name'];

                switch ($socialMediaName) {
                    case "instagram":
                        $socialMediaIcon = "fa fa-instagram";
                      break;
                    case "youtube":
                        $socialMediaIcon = "fa fa-youtube";
                      break;
                    case "tik-tok":
                        $socialMediaIcon = "fa fa-instagram";
                      break;
                    case "facebook":
                        $socialMediaIcon = "fa fa-facebook";
                      break;
                    case "snapchat":
                        $socialMediaIcon = "fa fa-snapchat-ghost";
                      break;
                    default:
                        $socialMediaIcon = "";
                  }
                $kolSocialData = new SocialMedia();
                $kolSocialData->user_id = $userId;
                $kolSocialData->profile_id = $profile_id[0];
                $kolSocialData->name = $requestMediaData['name'];
                $kolSocialData->social_icon =  $socialMediaIcon;
                $kolSocialData->social_user_id = $requestMediaData['social_user_id'];
                $kolSocialData->followers = $requestMediaData['followers'];
                $kolSocialMedia = $kolSocialData->save();
            }
        }        

        return $updateResponse; 
    }
    public function FeatureKolProfile($request)
    {
        
        $kol_profile_id = $request['kol_profile_id'];
        $is_featured = $request['is_featured'];
        $updateData = [];
        
        $updateData = [
            'is_featured' => $is_featured
        ];

        $updateResponse = KolProfile::where('id', $kol_profile_id)->update($updateData);

        return $updateResponse;
    }

    public function checkKolProfileExistOrNot($userId)
    {

        $profileData =  KolProfile::where('user_id', $userId)->with('getUser')->with('getDeal')->with('getSocialMedia')->first();
        $kolProfileDetails = [];

        if($profileData){
        $kolTypeName = $this->checkKolTypeExistOrNot($profileData['kol_type']);
        $kolProfileDetails['id'] = $profileData['id'];
        $kolProfileDetails['user_id'] = $profileData['user_id'];
        $kolProfileDetails['bio'] = $profileData['bio'];
        $kolProfileDetails['languages'] = $profileData['languages'];
        $kolProfileDetails['avatar'] = $profileData['avatar'];
        $kolProfileDetails['personal_email'] = $profileData['personal_email'];
        $kolProfileDetails['kol_type'] = $kolTypeName['name'];
        $kolProfileDetails['state'] = $profileData['state'];
        $kolProfileDetails['zip_code'] = $profileData['zip_code'];
        $kolProfileDetails['city'] = $profileData['city'];
        $kolProfileDetails['total_viewer'] = $profileData['total_viewer'];
        $kolProfileDetails['banner'] = $profileData['banner'];
        $kolProfileDetails['social_active'] = $profileData['social_active'];
        $socialMediaName = $profileData['social_active'];
        switch ($socialMediaName) {
            case "instagram":
                $socialMediaIcon = "fa fa-instagram";
                break;
                case "youtube":
                    $socialMediaIcon = "fa fa-youtube";
                    break;
                case "tik-tok":
                    $socialMediaIcon = "fa fa-instagram";
                    break;
                case "facebook":
                    $socialMediaIcon = "fa fa-facebook";
                    break;
                case "snapchat":
                    $socialMediaIcon = "fa fa-snapchat-ghost";
                    break;
                default:
                $socialMediaIcon = "";
            }
        $kolProfileDetails['social_active_icon'] = $socialMediaIcon;
        $kolProfileDetails['video_links'] = $profileData['video_links'];
        $kolProfileDetails['social_active'] = $profileData['social_active'];
        $kolProfileDetails['tags'] = $profileData['tags'];
        $kolProfileDetails['get_user'] = $profileData['getUser'];
        $kolProfileDetails['get_social_media'] = $profileData['getSocialMedia'];
        $kolProfileDetails['get_deal'] = $profileData['getDeal'];
        }
        
        return $kolProfileDetails;
    }

    public function checkKolProfileIdExistOrNot($profileId)
    {

        return KolProfile::where('id', $profileId)->first();
    }

    public function checkKolTypeExistOrNot($id)
    {

        return KolType::where('id', $id)->first();
    }

    public function checkAnnouncementExistOrNot($Id,$userId)
    {
        return Announcement::where('id', $Id)->where('user_id',$userId)->first();
    }

    public function checkDealExistOrNot($Id,$profileId)
    {
        return Deal::where('id', $Id)->where('kol_profile_id',$profileId)->first();
    }

    public function DealCount($profileId)
    {
        return Deal::where('kol_profile_id',$profileId)->where('status', 1)->count();
    }

    public function checkAddressExistOrNot($userId)
    {
        return Address::where('user_id',$userId)->first();
    }

    public function checkBannerExistOrNot($Id)
    {
        return Banner::where('id', $Id)->first();
    }

    public function checkFaqExistOrNot($Id)
    {
        return Faq::where('id', $Id)->first();
    }

    public function checkInformativeVideoExistOrNot($Id)
    {
        return InformativeVideo::where('id', $Id)->first();
    }

    public function checkBookmarkExistOrNot($endUserId,$kol_profile_id)
    {
        return Bookmark::where('kol_profile_id', $kol_profile_id)->where('end_user_id', $endUserId)->first();
    }

    public function checkFeedbackExistOrNot($endUserId,$kol_profile_id)
    {
        return Feedback::where('kol_profile_id', $kol_profile_id)->where('end_user_id', $endUserId)->first();
    }

    public function ViewKolProfileById($id)
    {
        $profileData = KolProfile::where('kol_profiles.id', $id)->with('getUser')->with('getSocialMedia')->with('getDeal')->get();
        $latestAnnouncement = Announcement::where('profile_id',$id)->where('status',1)->orderBy('id','Desc')->first();

        if($profileData->isEmpty()){
            return false;
        }

        $kolProfileData = $profileData;
        $kolProfileData[0]['announcement'] = $latestAnnouncement;
        
        return $kolProfileData;
    }
    
    public function KolProfileList($request){
        
        $pageNo = ($request['page']) ? $request['page'] : 1;
        $limit = ($request['limit']) ? $request['limit'] : 10;
        $sortBY = ($request['sortBY']) ? $request['sortBY'] : 'followers';
        $orderBy = isset($request['orderBy']) ? $request['orderBy'] : 'desc';
        $socialMedia = ($request['social_media']) ? $request['social_media'] : 'youtube';
        $UserIdByQuery = [];
        $sortBYQuery = [];
        if($sortBY && $socialMedia){
            $sortBYQuery = $this->sortFollowers($orderBy,$sortBY,$socialMedia);
        }
        if(isset($request['search']) && $request['search']!='' ){
            $UserIdByQuery = $this->searchUserByName($request['search']);
            
        }
        $loggedInUser = Auth::user()->id;

        $kolProfiles = KolProfile::with('getUser','getSocialMedia','getDeal')
        ->where(function($query) use ($request,$UserIdByQuery, $sortBYQuery, $sortBY, $socialMedia){
            if(isset($request['languages']) && !empty($request['languages'])){
                $query->whereRaw('Find_IN_SET(?, languages)', [$request['languages']]);
            }
            if(isset($request['search']) && $request['search']!= ''){
                $query->whereIn('user_id', [$UserIdByQuery][0]);
            }
            if(isset($request['state']) && $request['state']!= ''){
                $query->where('state', [$request['state']]);
            }            
            if(isset($request['stream']) && !empty($request['stream'])){
                $query->whereRaw('Find_IN_SET(?, social_active)', [$request['stream']]);
            }
            if(isset($request['kol_type']) && $request['kol_type']!=''){
                $query->where('kol_type', [$request['kol_type']]);
            }
        })->whereHas('getUser', function($query) use($request) {
            $query->where('role_id', '=', 2); // '=' is optional
        })->skip(($pageNo - 1) * $limit)->take($limit)->get();

        $kolProfilesCount = KolProfile::with('getUser','getSocialMedia','getDeal')
        ->where(function($query) use ($request,$UserIdByQuery){
            if(isset($request['languages']) && !empty($request['languages'])){
                $query->whereRaw('Find_IN_SET(?, languages)', [$request['languages']]);
            }
            if(isset($request['search']) && $request['search']!= ''){
                $query->whereIn('user_id', [$UserIdByQuery][0]);
            }
            if(isset($request['state']) && $request['state']!= ''){
                $query->where('state', [$request['state']]);
            }            
            if(isset($request['stream']) && !empty($request['stream'])){
                $query->whereRaw('Find_IN_SET(?, social_active)', [$request['stream']]);
            }
            if(isset($request['kol_type']) && $request['kol_type']!=''){
                $query->where('kol_type', [$request['kol_type']]);
            }
        })->whereHas('getUser', function($query) use($request) {
            $query->where('role_id', '=', 2); // '=' is optional
        })->count();
        
        $listProfiles = [];
        $listSocialMedia = [];
        $listDeals = [];
        $i = 0;
        
        foreach($kolProfiles as $key => $profileList){
            $listProfiles[$i]['profile_id'] = $profileList['id'];
            $kolBookmarked = Bookmark::where('end_user_id',$loggedInUser)->where('kol_profile_id',$profileList['id'])->first();
            $listProfiles[$i]['bookmark'] = ($kolBookmarked==null)? false : true;
            $listProfiles[$i]['languages'] = $profileList['languages'];
            $listProfiles[$i]['bio'] = $profileList['bio'];
            $listProfiles[$i]['avatar'] = $profileList['avatar'];
            $listProfiles[$i]['personal_email'] = $profileList['personal_email'];
            $listProfiles[$i]['kol_type'] = $profileList['kol_type'];
            $listProfiles[$i]['state'] = $profileList['state'];
            $listProfiles[$i]['city'] = $profileList['city'];
            $listProfiles[$i]['zip_code'] = $profileList['zip_code'];
            $listProfiles[$i]['total_viewer'] = $profileList['total_viewer'];
            $listProfiles[$i]['banner'] = $profileList['banner'];
            $listProfiles[$i]['social_active'] = $profileList['social_active'];
            $socialMediaName = $profileList['social_active'];
            switch ($socialMediaName) {
                case "instagram":
                    $socialMediaIcon = "fa fa-instagram";
                    break;
                case "youtube":
                    $socialMediaIcon = "fa fa-youtube";
                    break;
                case "tik-tok":
                    $socialMediaIcon = "fa fa-instagram";
                    break;
                case "facebook":
                    $socialMediaIcon = "fa fa-facebook";
                    break;
                case "snapchat":
                    $socialMediaIcon = "fa fa-snapchat-ghost";
                    break;
                default:
                    $socialMediaIcon = "";
                }
            $listProfiles[$i]['social_active_icon'] = $socialMediaIcon;
            $listProfiles[$i]['video_links'] = $profileList['video_links'];
            $listProfiles[$i]['tags'] = $profileList['tags'];
            $listProfiles[$i]['user_id'] = $profileList['getUser']['id'];
            $listProfiles[$i]['username'] = $profileList['getUser']['name'];
            $listProfiles[$i]['email'] = $profileList['getUser']['email'];
            $listProfiles[$i]['role_id'] = $profileList['getUser']['role_id'];
            $listProfiles[$i]['profile_image'] = $profileList['getUser']['avatar'];
            $listProfiles[$i]['gender'] = $profileList['getUser']['gender'];
            $listProfiles[$i]['phone'] = $profileList['getUser']['phone'];
            $listProfiles[$i]['deals'] = $profileList['getDeal'];
            // $listProfiles[$i]['bookmark'] = ($profileList['getBookmark']==null)? false : true;
            
            $j = 0;
            foreach($profileList['getSocialMedia'] as $socialAccounts){

                $listSocialMedia[$j]['social_media_id'] = $socialAccounts['id'];
                $listSocialMedia[$j]['social_platform'] = $socialAccounts['name'];
                $listSocialMedia[$j]['social_icon'] = $socialAccounts['social_icon'];
                $listSocialMedia[$j]['social_media_username'] = $socialAccounts['social_user_id'];
                $listSocialMedia[$j]['followers'] = $socialAccounts['followers'];
                $j++;
            }

            $listProfiles[$i]['SocialMedia'] = $listSocialMedia;

            $i++;
        }

        $finalArray = array();

        $finalArray['total'] = $kolProfilesCount;
        $finalArray['data'] = $listProfiles;

        return $finalArray;
    }
    
    public function getFeaturedProfileList($request){
        
        $kolProfiles = KolProfile::with('getUser','getSocialMedia', 'getBookmark', 'getFeedbacks','getAddress')->where('is_featured', 1)->where('status', 1)->get();
        $listProfiles = [];
        $listSocialMedia = [];
        $listFeedback = [];
        $i = 0;

        foreach($kolProfiles as $key => $profileList){
            $listProfiles[$i]['profile_id'] = $profileList['id'];
            $listProfiles[$i]['languages'] = $profileList['languages'];
            $listProfiles[$i]['is_featured'] = $profileList['is_featured'];
            $listProfiles[$i]['bio'] = $profileList['bio'];
            $listProfiles[$i]['avatar'] = $profileList['avatar'];
            $listProfiles[$i]['personal_email'] = $profileList['personal_email'];
            $listProfiles[$i]['kol_type'] = $profileList['kol_type'];
            $listProfiles[$i]['state'] = $profileList['state'];
            $listProfiles[$i]['city'] = $profileList['city'];
            $listProfiles[$i]['zip_code'] = $profileList['zip_code'];
            $listProfiles[$i]['total_viewer'] = $profileList['total_viewer'];
            $listProfiles[$i]['banner'] = $profileList['banner'];
            $listProfiles[$i]['social_active'] = $profileList['social_active'];
            $socialMediaName = $profileList['social_active'];
            switch ($socialMediaName) {
                case "instagram":
                    $socialMediaIcon = "fa fa-instagram";
                    break;
                case "youtube":
                    $socialMediaIcon = "fa fa-youtube";
                    break;
                case "tik-tok":
                    $socialMediaIcon = "fa fa-instagram";
                    break;
                case "facebook":
                    $socialMediaIcon = "fa fa-facebook";
                    break;
                case "snapchat":
                    $socialMediaIcon = "fa fa-snapchat-ghost";
                    break;
                default:
                    $socialMediaIcon = "";
                }
            $listProfiles[$i]['social_active_icon'] = $socialMediaIcon;
            $listProfiles[$i]['video_links'] = $profileList['video_links'];
            $listProfiles[$i]['tags'] = $profileList['tags'];
            $listProfiles[$i]['user_id'] = $profileList['getUser']['id'];
            $listProfiles[$i]['username'] = $profileList['getUser']['name'];
            $listProfiles[$i]['email'] = $profileList['getUser']['email'];
            $listProfiles[$i]['role_id'] = $profileList['getUser']['role_id'];
            $listProfiles[$i]['profile_image'] = $profileList['getUser']['avatar'];
            $listProfiles[$i]['gender'] = $profileList['getUser']['gender'];
            $listProfiles[$i]['phone'] = $profileList['getUser']['phone'];

            if($profileList['getAddress']){
                $listProfiles[$i]['Address']['address'] = $profileList['getAddress']['address'];
                $listProfiles[$i]['Address']['landmark'] = $profileList['getAddress']['landmark'];
                $listProfiles[$i]['Address']['zip'] = $profileList['getAddress']['zip'];
                $listProfiles[$i]['Address']['city'] = $profileList['getAddress']['city'];
                $listProfiles[$i]['Address']['state'] = $profileList['getAddress']['state'];
                $listProfiles[$i]['Address']['country'] = $profileList['getAddress']['country'];
            }
            
            $listProfiles[$i]['bookmark'] = ($profileList['getBookmark']==null)? false : true;

            if($profileList['getSocialMedia']){
            $j = 0;
            foreach($profileList['getSocialMedia'] as $socialAccounts){
                $listSocialMedia[$j]['social_media_id'] = $socialAccounts['id'];
                $listSocialMedia[$j]['social_platform'] = $socialAccounts['name'];
                $listSocialMedia[$j]['social_icon'] = $socialAccounts['social_icon'];
                $listSocialMedia[$j]['social_media_username'] = $socialAccounts['social_user_id'];
                $listSocialMedia[$j]['followers'] = $socialAccounts['followers'];
                $j++;
            }
            }

            if($profileList['getFeedbacks']){
                $k=0;
                foreach($profileList['getFeedbacks'] as $feedbacks){
                    $listFeedback[$k]['feedback_id'] = $feedbacks['id'];
                    $listFeedback[$k]['end_user_id'] = $feedbacks['end_user_id'];
                    $listFeedback[$k]['kol_profile_id'] = $feedbacks['kol_profile_id'];
                    $listFeedback[$k]['comment'] = $feedbacks['comment'];
                    $listFeedback[$k]['rating'] = $feedbacks['rating'];
                    $userImage = User::where('id',$feedbacks['end_user_id'])->first();
                    $listFeedback[$k]['end_user_image'] = $userImage['avatar'];
                    $k++;
                }
            }
         
            $listProfiles[$i]['SocialMedia'] = $listSocialMedia;
            $listProfiles[$i]['Feedbacks'] = $listFeedback;

            $i++;
        }
        
        return $listProfiles;
    }


    public function searchUserByName($search){
        return User::where('name', 'like', '%'.$search.'%')->pluck('id');
    } 
    
    public function getUserRoleById($userId){
        return User::select('role_id')->where('id', $userId)->first();
    }

    public function getMsgById($msgId,$userId)
    {
        return ChatThread::where('id', $msgId)
        ->where('status',1)
        ->where('sender_id',$userId)->first();
    }

    public function sortFollowers($orderBy,$sortBY,$socialMedia){
        return SocialMedia::where('name',$socialMedia)->orderBy($sortBY,$orderBy)->pluck('profile_id');
        
    }
}

