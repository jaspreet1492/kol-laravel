<?php

namespace  App\Http\Services;

use App\Models\User;
use App\Models\UserTokens;
use App\Models\UserAddress;
use App\Models\KolProfile;
use App\Models\SocialMedia;
use App\Http\Controllers\MailController;
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
            // $input = $request->only('email', 'new_password');
            $input = [];
            $input['email'] = $email;
            $input['password'] = $request['new_password'];
            $token = JWTAuth::attempt($input);
            // // Store token in user tokens table
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
        // dd($token);
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
        $kolProfileData->total_viewer = $request['total_viewer'];
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
                $kolSocialData = new SocialMedia();
                $kolSocialData->user_id = $userId;
                $kolSocialData->profile_id = $lastProfileId;
                $kolSocialData->name = $requestMediaData['name'];
                $kolSocialData->social_user_id = $requestMediaData['social_user_id'];
                $kolSocialData->followers = $requestMediaData['followers'];
                $kolSocialMedia = $kolSocialData->save();
            }
        }
        return $lastProfileId;
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
        
        $updateResponse = KolProfile::where('id', $id)->update($updateData);
        if ($updateResponse) {
            $socialMedia = SocialMedia::where('profile_id', $id)->where('user_id',$userId)->count();

            if($socialMedia>0){
                $socialAccounts = SocialMedia::where('profile_id', $id)->where('user_id',$userId)->delete();
            }
            foreach ($request['social_media'] as $requestMediaData) {
                $kolSocialData = new SocialMedia();
                $kolSocialData->user_id = $userId;
                $kolSocialData->profile_id = $id;
                $kolSocialData->name = $requestMediaData['name'];
                $kolSocialData->social_user_id = $requestMediaData['social_user_id'];
                $kolSocialData->followers = $requestMediaData['followers'];
                $kolSocialMedia = $kolSocialData->save();
            }
        }

        return $updateResponse;
    }

    public function checkKolProfileExistOrNot($userId)
    {

        return KolProfile::where('user_id', $userId)->first();
    }

    public function ViewKolProfileById($id)
    {
        $profileData = KolProfile::where('kol_profiles.id', $id)->with('getSocialMedia')->get();

        return $profileData;
    }
}

