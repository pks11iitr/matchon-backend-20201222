<?php

namespace App\Http\Controllers\MobileApps\AdminUsersApp\Auth;

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
            Msg91::send($user->mobile,$msg, env('OTP_MSG'));
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
        $user=Customer::where('mobile', $request->mobile)->where('account_type', 'ADMIN')->first();

        if(!$user){
            //return $request->all();
            //return ['status'=>'failed', 'message'=>'This account is not registered with us. Please signup to continue'];
//            $user=Customer::create([
//               'mobile'=>$request->mobile,
//               'password'=>'none'
//            ]);
            return [
                'status'=>'failed',
                'message'=>'Account is not registered'
            ];
        }

        if(!in_array($user->status, [0,1]))
            return ['status'=>'failed', 'message'=>'This account has been blocked'];

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

}
