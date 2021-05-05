<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OTPModel extends Model
{
    protected $table='otps';

    protected $fillable=['entity', 'user_id', 'otp', 'type', 'expiry'];

    public static function createOTP($entity,$userid, $type){
        $rand=rand(1, 9).''.rand(1, 9).''.rand(1, 9).''.rand(1, 9).''.rand(1, 9).''.rand(1, 9);
        $otp=self::where('entity', $entity)
                        ->where('user_id', $userid)
                        ->where('isverified', false)
                        ->where('type', $type)
                        ->orderBy('id', 'desc')
                        ->first();
        if($otp){
            return $otp->otp;
        }
        $otp=self::create(['entity'=>$entity, 'user_id'=>$userid, 'otp'=>$rand, 'type'=>$type, 'expiry'=>date('Y-m-d H:i:s')]);
        if($otp)
            return $otp->otp;

        return false;
    }

    public static function verifyOTP($entity, $userid, $type, $otp){
        $otpobj=self::where('entity', $entity)
            ->where('user_id', $userid)
            ->where('isverified', false)
            ->where('type', $type)
            ->orderBy('id', 'desc')
            ->first();

        if(!$otpobj || $otpobj->otp!=$otp){
            return false;
        }

        $otpobj->isverified=true;
        if($otpobj->save())
            return true;

        return false;

    }

}
