<?php

namespace App\Models;

use App\Models\Traits\Active;
use App\Models\Traits\DocumentUploadTrait;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Kodeine\Acl\Traits\HasRole;
use NotificationChannels\Fcm\Exceptions\CouldNotSendNotification;
use Tymon\JWTAuth\Contracts\JWTSubject;
use DateTime;
class Customer extends Authenticatable implements JWTSubject
{
    use DocumentUploadTrait, Notifiable, Active;

    protected $table='customers';

    protected $fillable = [
        'name', 'email', 'mobile', 'password', 'image', 'dob', 'address','country_id', 'city_id', 'state_id','pincode', 'status','notification_token', 'gender', 'education_id', 'occupation_id', 'employement_id', 'salaray_id', 'religion_id', 'height_id', 'language_id', 'marital_status_id', 'salary_id', 'about_me','image','height_feet', 'from_age', 'to_age','from_distance','to_distance','pref_gender','smoking','marijuana','drugs','drinking','job_id','ethicity_id', 'account_type', 'rate', 'country', 'country_flag','age_show','distance_show','plan_id','membership_expiry', 'interests', 'from_height','to_height', 'system_messages','short_video'];

    protected $hidden = [
        'password','created_at','deleted_at','updated_at','email','mobile'
    ];

    protected $appends=['age', 'last_seen'];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Specifies the user's FCM token
     *
     * @return string|array
     */
    public function routeNotificationForFcm()
    {
        return $this->notification_token;
    }

    public function notify($instance)
    {
        try{
            app(Dispatcher::class)->send($this, $instance);

        }catch(CouldNotSendNotification $e){

        }

    }

    public function isPremiumUser(){
        if(CoinWallet::balance($this->user_id)>0){
            return true;
        }
    }

    public function getImageAttribute($value){
        if($value)
            return Storage::url($value);
        return Storage::url('customers/default.jpeg');
    }

    public function getShortVideoAttribute($value){
        if($value)
            return Storage::url($value);
        return null;
    }

    /*public function getImageAttribute($value){
        if($value)
            return Storage::url($value);
        return Storage::url('customers/default.jpeg');
    }*/


    public function Height(){
        return $this->belongsTo('App\Models\Height', 'height_id');
    }
    public function Ethnicity(){
        return $this->belongsTo('App\Models\EthniCity', 'ethicity_id');
    }
//    public function Kids(){
//        return $this->belongsTo('App\Models\Kid', 'kids_id');
//    }
//    public function Family(){
//        return $this->belongsTo('App\Models\FamilyPlan', 'family_id');
//    }

    public function Plan(){
        return $this->belongsTo('App\Models\Membership', 'plan_id');
    }

    public function Work(){
        return $this->belongsTo('App\Models\Ocupation', 'occupation_id');
    }
    public function Job(){
        return $this->belongsTo('App\Models\Employment', 'job_id');
    }
    public function Education(){
        return $this->belongsTo('App\Models\Education', 'education_id');
    }
//    public function AttendedLavel(){
//        return $this->belongsTo('App\Models\AttendedLavel', 'attended_lave_id');
//    }

    public function Religion(){
        return $this->belongsTo('App\Models\Religion', 'religion_id');
    }
//    public function Politics(){
//        return $this->belongsTo('App\Models\Politics', 'politics_id');
//    }
//
//
    public function countryName(){
        return $this->belongsTo('App\Models\Country', 'country');
    }
//    public function city(){
//        return $this->belongsTo('App\Models\City', 'city_id');
//    }
//    public function state(){
//        return $this->belongsTo('App\Models\State', 'state_id');
//    }
//
//
//    public function salary(){
//        return $this->belongsTo('App\Models\Income', 'salary_id');
//    }

    public function getAgeAttribute($value){
        if($this->dob)
            return $this->getAgeDifference($this->dob);
        return '--';
    }

    function getAgeDifference($date){

        $text='--';

        if($date){
            $date1 = new DateTime(date('Y-m-d H:i:s'));
            $date2 = $date1->diff(new DateTime($date));

            $text='';

            if($date2->y)
                $text=$text.$date2->y;
//                $text=$text.$date2->y.' year'.' ';

//            if($date2->m)
//                $text=$text.$date2->m.' month';
        }

        return $text;
    }

    public function getLastSeenAttribute($value){
        $date=$this->last_active;

        if($date){
            $date1 = new DateTime(date('Y-m-d H:i:s'));
            $date2 = $date1->diff(new DateTime($date));

            if($date2->y)
                return $date2->y.' yrs ago';

            if($date2->m)
                return $date2->m.' months ago';

            if($date2->d)
                return $date2->d.' days ago';

            if($date2->h)
                return $date2->h.' hours ago';

            if($date2->i)
                return $date2->i.' mins ago';

            return 'now';

        }

    }

    public function likesdislikes(){
        return $this->hasMany('App\Models\LikeDislike', 'receiver_id');
    }

    public function likeddisliked(){
        return $this->hasMany('App\Models\LikeDislike', 'sender_id');
    }

    public function interests(){
        return $this->belongsToMany('App\Models\Interest', 'user_interests', 'user_id','interest_id');
    }

//    public function getShortVideoAttribute($value){
//        if($value)
//            return Storage::url($value);
//
//        return null;
//    }

}
