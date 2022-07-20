<?php

namespace  App\Http\Services;

use App\Models\User;
use App\Models\UserTokens;
use App\Models\UserAddress;
use App\Models\KolProfile;
use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\Announcement;
use App\Models\SocialMedia;
use App\Models\Feedback;
use App\Models\Banner;
use App\Http\Controllers\MailController;
use App\Models\KolType;
use App\Models\Bookmark;
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
    // $data =[];
    // dd($chatData);
    
    foreach($chatData as $chatDatas){
        $obj = [];
            // $obj['userData'] = $chatDatas->getUser;
            $obj['name']=$chatDatas->getSender->name;
            $obj['last_name']=$chatDatas->getSender->last_name;
            // $obj['userData']['role_id']=$chatDatas->getReceiver->role_id;
            $obj['avatar']=(isset($chatDatas->kolProfile->avatar)&& $chatDatas->kolProfile->avatar!=NULL)?$chatDatas->kolProfile->avatar:$chatDatas->getReceiver->avatar;
            // $obj['userData']['email']=$chatDatas->getReceiver->email;
            $obj['message_id'] = $chatDatas->id;
            $obj['sender_id'] = $chatDatas->sender_id;
            $obj['receiver_id'] = $chatDatas->receiver_id;
            $obj['message'] = $chatDatas->message;
            $obj['sent_at'] = $chatDatas->created_at;
            $obj['edit_at'] = $chatDatas->updated_at;
        
        $data[] = $obj;
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
        return User::where('id', $userId)->first();
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

        $profileImgUrl = KolProfile::makeImageUrl($request['avatar']);
        $bannerImgUrl = KolProfile::makeImageUrl($request['banner']);
        $kolProfileData = new KolProfile();
        $kolProfileData->user_id = $userId;
        $kolProfileData->languages = implode(',', $request['languages']);
        $kolProfileData->bio = $request['bio'];
        $kolProfileData->personal_email = $request['personal_email'];
        $kolProfileData->kol_type = $request['kol_type'];
        $kolProfileData->state = $request['state'];
        $kolProfileData->zip_code = $request['zip_code'];
        $kolProfileData->city = $request['city'];
        $kolProfileData->social_active = implode(',', $request['social_active']);
        $kolProfileData->video_links = implode(',', $request['video_links']);
        $kolProfileData->tags = implode(',', $request['tags']);
        $kolProfileData->avatar = $profileImgUrl;
        $kolProfileData->banner = $bannerImgUrl;
        $kolProfileData->status = 1;
        $checkUserSaved = $kolProfileData->save();
        $lastProfileId = $kolProfileData->id;

        if ($lastProfileId) {
            foreach ($request['social_media'] as $requestMediaData) {
                $newArr =  json_decode($requestMediaData, true);
                $requestMediaData = $newArr[0];
                $kolSocialData = new SocialMedia();
                $kolSocialData->user_id = $userId;
                $kolSocialData->profile_id = $lastProfileId;
                $kolSocialData->name = $requestMediaData['name'];
                $kolSocialData->social_icon = $requestMediaData['social_icon'];
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
        $AnnouncementImg = Announcement::makeImageUrl($request['image']);
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
        $AnnouncementImg = ($request['image']) ? Announcement::makeImageUrl($request['image']) : NULL;
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

    // Update Banner
    public function UpdateBanner($request, $id)
    {
        $id = ($request['id']) ? $request['id'] : NULL;
        $Banner = ($request['image']) ? Banner::makeImageUrl($request['image']) : NULL;
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

    public function getAnnouncementList($userId){

        $AnnouncementList = Announcement::where('user_id',$userId)->where('status',1)->with('getUser')->get();

        return $AnnouncementList;
    }

    public function getBannerList(){

        $BannerList = Banner::where('status',1)->get();

        return $BannerList;
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

    public function getAllAnnouncementList(){

        $AnnouncementList = Announcement::where('status',1)->with('getUser')->get();

        return $AnnouncementList;
    }

    public function deleteAnnouncement($id){

        $Announcement = Announcement::where('id',$id)->delete();

        return $Announcement;
    }
    public function deleteBanner($id){

        $Banner = Banner::where('id',$id)->delete();

        return $Banner;
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

        $id = ($request['id']) ? $request['id'] : NULL;
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
                'social_active' => implode(',', $request['social_active']),
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
                'social_active' => implode(',', $request['social_active']),
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
                'social_active' => implode(',', $request['social_active']),
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
                'social_active' => implode(',', $request['social_active']),
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
                $newArr =  json_decode($requestMediaData, true);
                $requestMediaData = $newArr[0];
                $kolSocialData = new SocialMedia();
                $kolSocialData->user_id = $userId;
                $kolSocialData->profile_id = $profile_id[0];
                $kolSocialData->name = $requestMediaData['name'];
                $kolSocialData->social_icon = $requestMediaData['social_icon'];
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

        return KolProfile::where('user_id', $userId)->first();
    }
    public function checkKolProfileIdExistOrNot($profileId)
    {

        return KolProfile::where('id', $profileId)->first();
    }

    public function checkKolTypeExistOrNot($id)
    {

        return KolType::where('id', $id)->first();
    }

    public function checkAnnouncementExistOrNot($Id)
    {
        return Announcement::where('id', $Id)->first();
    }

    public function checkBannerExistOrNot($Id)
    {
        return Banner::where('id', $Id)->first();
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
        $profileData = KolProfile::where('kol_profiles.id', $id)->with('getUser')->with('getSocialMedia')->get();
        $latestAnnouncement = Announcement::where('profile_id',$id)->where('status',1)->orderBy('id','Desc')->first();
        
        
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
        
        $kolProfiles = KolProfile::with('getUser','getSocialMedia', 'getBookmark')
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
            // if($sortBY && $socialMedia){
            //     $query->whereIn('id', [$sortBYQuery][0]);            
            // }
            if(isset($request['stream']) && !empty($request['stream'])){
                $query->whereRaw('Find_IN_SET(?, social_active)', [$request['stream']]);
            }
            if(isset($request['kol_type']) && $request['kol_type']!=''){
                $query->where('kol_type', [$request['kol_type']]);
            }
        })->skip(($pageNo - 1) * $limit)->take($limit)->get();
        $listProfiles = [];
        $listSocialMedia = [];
        $i = 0;

        foreach($kolProfiles as $key => $profileList){
            $listProfiles[$i]['profile_id'] = $profileList['id'];
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
            $listProfiles[$i]['video_links'] = $profileList['video_links'];
            $listProfiles[$i]['tags'] = $profileList['tags'];
            $listProfiles[$i]['user_id'] = $profileList['getUser']['id'];
            $listProfiles[$i]['username'] = $profileList['getUser']['name'];
            $listProfiles[$i]['email'] = $profileList['getUser']['email'];
            $listProfiles[$i]['role_id'] = $profileList['getUser']['role_id'];
            $listProfiles[$i]['profile_image'] = $profileList['getUser']['avatar'];
            $listProfiles[$i]['gender'] = $profileList['getUser']['gender'];
            $listProfiles[$i]['phone'] = $profileList['getUser']['phone'];
            $listProfiles[$i]['bookmark'] = ($profileList['getBookmark']==null)? false : true;
            
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
        
        return $listProfiles;
    }
    
    public function getFeaturedProfileList($request){
        
       
        $kolProfiles = KolProfile::with('getUser','getSocialMedia', 'getBookmark', 'getFeedbacks')->where('is_featured', 1)->where('status', 1)->get();
        $listProfiles = [];
        $listSocialMedia = [];
        $listFeedback = [];
        $i = 0;
        //dd($kolProfiles);
        foreach($kolProfiles as $key => $profileList){
            // dd($profileList);
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
            $listProfiles[$i]['video_links'] = $profileList['video_links'];
            $listProfiles[$i]['tags'] = $profileList['tags'];
            $listProfiles[$i]['user_id'] = $profileList['getUser']['id'];
            $listProfiles[$i]['username'] = $profileList['getUser']['name'];
            $listProfiles[$i]['email'] = $profileList['getUser']['email'];
            $listProfiles[$i]['role_id'] = $profileList['getUser']['role_id'];
            $listProfiles[$i]['profile_image'] = $profileList['getUser']['avatar'];
            $listProfiles[$i]['gender'] = $profileList['getUser']['gender'];
            $listProfiles[$i]['phone'] = $profileList['getUser']['phone'];
            $listProfiles[$i]['bookmark'] = ($profileList['getBookmark']==null)? false : true;
            // dd($profileList['getFeedbacks']);
            $j = 0;
            foreach($profileList['getSocialMedia'] as $socialAccounts){
                $listSocialMedia[$j]['social_media_id'] = $socialAccounts['id'];
                $listSocialMedia[$j]['social_platform'] = $socialAccounts['name'];
                $listSocialMedia[$j]['social_icon'] = $socialAccounts['social_icon'];
                $listSocialMedia[$j]['social_media_username'] = $socialAccounts['social_user_id'];
                $listSocialMedia[$j]['followers'] = $socialAccounts['followers'];
                $j++;
            }
            $k=0;
            
            foreach($profileList['getFeedbacks'] as $feedbacks){
                $listFeedback[$k]['feedback_id'] = $feedbacks['id'];
                $listFeedback[$k]['end_user_id'] = $feedbacks['end_user_id'];
                $listFeedback[$k]['kol_profile_id'] = $feedbacks['kol_profile_id'];
                $listFeedback[$k]['comment'] = $feedbacks['comment'];
                $listFeedback[$k]['rating'] = $feedbacks['rating'];
                $k++;
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

    public function sortFollowers($orderBy,$sortBY,$socialMedia){
        return SocialMedia::where('name',$socialMedia)->orderBy($sortBY,$orderBy)->pluck('profile_id');
        
    }
}

