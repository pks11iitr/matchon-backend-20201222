<?php

namespace App\Http\Controllers\MobileApps;

use App\Http\Controllers\Controller;
use App\Models\CoinWallet;
use App\Models\Customer;
use App\Models\LikeDislike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchesController extends Controller
{
    public function findMatches(Request $request){

        $user=$request->user;

        $profiles=Customer::with('gallery', 'countryName', 'Height')
            ->select('id', 'name','image', 'dob','country', 'country_flag', 'height_id', 'last_active', 'short_video', 'rate')
            ->where('id', '!=', $user->id)
            ->where('account_type', 'ADMIN');

//        if($user->pref_gender=='Male')
//            $profiles=$profiles->where('gender', 'Male');
//        else
//            $profiles=$profiles->where('gender', 'Female');
//
//        if($user->from_height && $user->to_height){
//            $profiles=$profiles->whereHas('height', function($height)use($user){
//                $height->where('numeric_height', '>=', $user->from_height)->where('numeric_height', '<=', $user->to_height);
//            });
//        }
//
//        if($user->from_age){
//            $profiles=$profiles
//                ->whereNotNull('dob')
//                ->where(DB::raw("DATEDIFF('".date('Y-m-d')."', dob ) / 365"), '>=', $user->from_age);
//        }
//
//        if($user->to_age){
//            $profiles=$profiles
//            ->whereNotNull('dob')
//                ->where(DB::raw("DATEDIFF('".date('Y-m-d')."', dob ) / 365"), '<=', $user->to_age);
//        }

        //$profiles=$profiles->inRandomOrder();
        if($request->country_id){
            $profiles=$profiles->where('country', $request->country_id);
        }

        $profiles=$profiles
            ->orderBy('last_active', 'desc')
            ->paginate(10);

        $next_page_url=$profiles->nextPageUrl();
        $prev_page_url=$profiles->previousPageUrl();

        $like=LikeDislike::where('sender_id', $user->id)->select('receiver_id', 'type')->get();
        $likes=[];
        foreach ($like as $l){
            $likes[$l->receiver_id]=$l->type;
        }

        foreach($profiles as $d)
        {
            if(isset($likes[$d->id])){
                $d->like_status=$likes[$d->id];
            }else{
                $d->like_status=2;
            }

            if(date('Y-m-d H:i:s', strtotime('+1 minutes',strtotime($d->last_active)))>date('Y-m-d H:i:s'))
                $d->is_online=1;
            else
                $d->is_online=0;
        }


        return [

            'status'=>'success',
            'message'=>'',
            'apidata'=>compact('profiles', 'next_page_url', 'prev_page_url')


        ];


    }


    public function matchDetails(Request $request, $id){
        $user=$request->user;

        $details=Customer::with(['gallery', 'Height', 'Ethnicity', 'Education', 'Job', 'Work', 'Religion', 'interests'])
            ->select('id', 'name', 'image', 'mobile', 'gender', 'dob', 'email', 'about_me', 'height_id', 'ethicity_id', 'education_id', 'occupation_id', 'job_id', 'religion_id', 'drinking', 'smoking', 'marijuana', 'drugs', 'rate')
            ->findOrFail($id);

        $like=LikeDislike::where('sender_id', $user->id)
            ->where('receiver_id', $id)
            ->first();

        $like_status=isset($like)?$like->type:2;

        $likes_count=LikeDislike::where('receiver_id', $id)->count();

        $gifts=CoinWallet::with('gift')
                ->where('receiver_id', $details->id)
                ->where('gift_id', '!=', null)
                ->groupBy('gift_id')
                ->select(DB::raw('count(*) as count'), 'gift_id')
                ->get();

        return [
            'status'=>'success',
            'data'=>compact('details', 'like_status', 'gifts', 'likes_count')
        ];
    }
}
