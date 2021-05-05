<?php

namespace App\Http\Controllers\MobileApps\Auth;

use App\Events\SendOtp;
use App\Models\Customer;
use App\Models\OTPModel;
use App\Models\User;
use App\Services\SMS\Msg91;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    //adding fgsdsd
    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function userId(Request $request, $type='password')
    {
        if(filter_var($request->user_id, FILTER_VALIDATE_EMAIL))
            return 'email';
        else
            return 'mobile';
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'user_id' => $this->userId($request)=='email'?'required|email|string|exists:customers,email':'required|digits:10|string|exists:customers,mobile',
            'password' => 'required|string',
        ], ['user_id.exists'=>'This account is not registered with us. Please signup to continue']);
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($token=$this->attemptLogin($request)) {
            return $this->sendLoginResponse($this->getCustomer($request), $token);
        }
        return [
            'status'=>'failed',
            'token'=>'',
            'message'=>'Credentials are not correct'
        ];

    }


    protected function attemptLogin(Request $request)
    {
        return Auth::guard('customerapi')->attempt(
            [$this->userId($request)=>$request->user_id, 'password'=>$request->password]
        );
    }

    protected function getCustomer(Request $request){
        $customer=Customer::where($this->userId($request),$request->user_id)->first();
        $customer->notification_token=$request->notification_token;
        $customer->save();
        return $customer;
    }

    protected function sendLoginResponse($user, $token){
        if($user->status==0){
            $otp=OTPModel::createOTP('customer', $user->id, 'login');
            $msg=str_replace('{{otp}}', $otp, config('sms-templates.login'));
            Msg91::send($user->mobile,$msg);
            return ['status'=>'success', 'message'=>'otp verify', 'token'=>''];
        }
        else if($user->status==1)
            return ['status'=>'success', 'message'=>'Login Successfull', 'token'=>$token];
        else
            return ['status'=>'failed', 'message'=>'This account has been blocked', 'token'=>''];
    }


    /**
     * Handle a login request to the application with otp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function loginWithOtp(Request $request){
        $this->validateOTPLogin($request);
        //die('aaad');
        $user=Customer::where('mobile', $request->mobile)->first();

        if(!$user){
            //return $request->all();
            //return ['status'=>'failed', 'message'=>'This account is not registered with us. Please signup to continue'];
            $user=Customer::create([
               'mobile'=>$request->mobile,
               'password'=>'none',
                'user_id'=>'MCN'.time()
            ]);
        }

        if(!in_array($user->status, [0,1]))
            return ['status'=>'failed', 'message'=>'This account has been blocked'];

        if(!$user->sendbird_token){
            //register on sendbird app
            $sendbird=app('App\Services\SendBird\SendBird');
            $response=$sendbird->createUser($user);

            if(isset($response['user_id'])){
                $user->sendbird_token=$response['access_token']??null;
                $user->save();
            }else{
                return ['status'=>'failed', 'message'=>'Something went wrong please. Please try again'];
            }
        }

        $otp=OTPModel::createOTP('customer', $user->id, 'login');
        $msg=str_replace('{{otp}}', $otp, config('sms-templates.login'));
        event(new SendOtp($user->mobile, $msg, env('OTP_MSG')));

        return ['status'=>'success', 'message'=>'Please verify OTP to continue'];
    }


    protected function validateOTPLogin(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10|string',
        ]);
    }

    public function googleLogin(Request $request){
        $request->validate([
            'google_token'=>'required',
            'notification_token'=>'required',
        ]);

        $client= new \Google_Client(['client_id'=>env('GOOGLE_WEB_CLIENT_ID')]);
        $payload = $client->verifyIdToken($request->google_token);
        if (!isset($payload['email'])) {
            return [
                'status'=>'failed',
                'message'=>'Invalid Token Request'
            ];
        }
        $email=$payload['email'];
        $name=$payload['name']??'';
        $picture=$payload['picture']??'';

        $user=Customer::where('email', $email)->first();
        if(!$user){
            $user=Customer::create([
                'email'=>$email,
                'name'=>$name,
                'status'=>1,
                'user_id'=>'MCN'.time(),
                'password'=>'none',
                //'notification_token'=>$request->notification_token
            ]);
        }

        if(!in_array($user->status, [0,1]))
            return ['status'=>'failed', 'message'=>'This account has been blocked'];

        if(!$user->sendbird_token){
            //register on sendbird app
            $sendbird=app('App\Services\SendBird\SendBird');
            $response=$sendbird->createUser($user);

            if(isset($response['user_id'])){
                $user->sendbird_token=$response['access_token']??null;
                $user->save();
            }else{
                return ['status'=>'failed', 'message'=>'Something went wrong please. Please try again'];
            }
        }


        $user->notification_token=$request->notification_token;
        $user->save();

        return [
            'status'=>'success',
            'message'=>'OTP has been verified successfully',
            'token'=>Auth::guard('customerapi')->fromUser($user),
            'user_id'=>'Matchon'.$user->id,
            'sendbird_token'=>$user->sendbird_token
        ];


    }

}
