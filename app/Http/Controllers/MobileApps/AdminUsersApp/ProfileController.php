<?php

namespace App\Http\Controllers\MobileApps\AdminUsersApp;

use App\Http\Controllers\Controller;
use App\Models\AttendedLavel;
use App\Models\City;
use App\Models\Country;
use App\Models\Document;
use App\Models\Earning;
use App\Models\Education;
use App\Models\Employment;
use App\Models\EthniCity;
use App\Models\FamilyPlan;
use App\Models\Height;
use App\Models\Income;
use App\Models\Interest;
use App\Models\Kid;
use App\Models\Languages;
use App\Models\Membership;
use App\Models\Ocupation;
use App\Models\Politics;
use App\Models\State;
use App\Models\Religion;

use Illuminate\Http\Request;

class ProfileController extends Controller
{


    public function getprofile(Request $request){
        $user=$request->user;
//        $profile=array(
//                      'id'=>$user->id,
//                      'image'=>$user->image,
//                      'gender'=>$user->gender,
//                      'dob'=>$user->dob,
//                      'mobile'=>$user->mobile,
//                      'email'=>$user->email,
//                      'address'=>$user->address,
//                      'about_me'=>$user->about_me,
//                      'height'=>$user->Height->name??'',
//                      'ethnicity'=>$user->Ethnicity->name??'',
//                      //'kid'=>$user->Kids->name??'',
//                      //'family_plan'=>$user->Family->name??'',
//                      'work'=>$user->Work->name??'',
//                      'job'=>$user->Job->name??'',
//                      'education'=>$user->Education->name??'',
//                      //'attendedlavel'=>$user->AttendedLavel->name??'',
//                      'religion'=>$user->Religion->name??'',
//                      //'Politics'=>$user->Politics->name??'',
//                       'drinking'=>$user->drinking,
//                       'smoking'=>$user->smoking,
//                       'marijuana'=>$user->marijuana,
//                       'drugs'=>$user->drugs,
//                       'age_show'=>$user->age_show,
//                       'distance_show'=>$user->distance_show,
//
//            );

        $height=$user->Height->name??'';

        $coins=Earning::where('user_id', $user->id)->sum('coins');

        $ethnicity=$user->Ethnicity->name??'';

        $profile=$user->only('id','name','gender', 'dob', 'mobile', 'email', 'about_me', 'height_id', 'ethicity_id', 'education_id', 'occupation_id', 'job_id', 'religion_id', 'drinking', 'smoking', 'marijuana', 'drugs','age_show', 'distance_show', 'image', 'interests','age');

        $profile['coins']=$coins;
        $profile['height']=$height;
        $profile['ethnicity']=$ethnicity;


        $height=Height::select('name', 'id')->get();
        ///  $language=Languages::select('name', 'id')->get();
        $country=Country::select('name', 'id')->get();
//        $state=State::select('name', 'id')->get();
        $ethnicity=EthniCity::select('name', 'id')->get();
        //$kids=Kid::select('name', 'id')->get();
        //$familyplan=FamilyPlan::select('name', 'id')->get();
        $occupation=Ocupation::select('name', 'id')->get();
        $employment=Employment::select('name', 'id')->get();
        $education=Education::select('name', 'id')->get();
        //$attended=AttendedLavel::select('name', 'id')->get();
        //$politics=Politics::select('name', 'id')->get();

        // $income=Income::select('name', 'id')->get();
        $religion=Religion::select('name', 'id')->get();
        //$marital=config('myconfig.marrital');

        return [

            'status'=>'success',
            'message'=>'',
            'profile'=>$profile,
            'data'=>compact('height','ethnicity', 'occupation','employment','education', 'religion', 'country')

        ];


    }

    public function updateprofile(Request $request){
        $user=$request->user;

        $user->update($request->only('name','gender', 'dob', 'email', 'about_me', 'height_id', 'ethicity_id', 'education_id', 'occupation_id', 'job_id', 'religion_id', 'drinking', 'smoking', 'marijuana', 'drugs','age_show', 'distance_show', 'interests'));

        $sendbird=app('App\Services\SendBird\SendBird');
        $sendbird->updateUser($user);

        return [
            'status'=>'success',
            'message'=>'Profile has been updated',
            'data'=>[]
        ];

    }

    public function settings(Request $request){
        $user=$request->user;

        $memberships=Membership::active()
            ->select('title', 'description', 'price')
            ->get();

        return [

            'status'=>'success',
            'message'=>'',
            'data'=>compact('user', 'memberships')


        ];

    }

//get user images
    public function picures(Request $request){
        $user=$request->user;

        $images=$user->gallery()->select('id', 'file_path')->get();
        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('images')
        ];
    }

//upload user images
    public function uploadpictures(Request $request){

        $request->validate([
            'images'=>'required|array',
        ]);

        $user=$request->user;
        foreach($request->images as $image)
            $document=$user->saveDocument($image,'profile');

        //$images=$user->gallery()->select('id', 'file_path')->get();

        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('document')
        ];
    }
//delete pic
    public function deletepic(Request $request, $id){
        $user=$request->user;
        Document::where('id', $id)
            ->where('entity_type', 'App\Models\Customer')
            ->where('entity_id', $user->id)
            ->delete();

        $images=$user->gallery()->select('id', 'file_path')->get();

        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('images')
        ];
    }
    //update picture on profile pic
    public function updateProfilePic(Request $request, $id){
        $user=$request->user;
        $document=Document::where('entity_type', 'App\Models\Customer')
            ->where('entity_id', $user->id)
            ->find($id);

        //var_dump($document->isDirty());die;

        //echo $document->getRawOriginal('file_path');die;
        if(!$document)
            return [
                'status'=>'failed',
                'message'=>'invalid request',
                'data'=>[]
            ];

        $user->image=$document->getRawOriginal('file_path');
        $user->save();

        $sendbird=app('App\Services\SendBird\SendBird');
        $sendbird->updateUser($user);

        return [
            'status'=>'success',
            'message'=>'success',
            'data'=>[
                'image'=>$document->file_path
            ]
        ];
    }

    public function updateAboutMe(Request $request){

        $result=$request->user->update($request->only('about_me'));

        if($request->image)
            $request->user->saveImage($request->image, 'customers');

        if($result){
            return [
                'status'=>'success',
                'message'=>'Profile Has Been Updated',
                'data'=>[]
            ];
        }else{
            return [
                'status'=>'failed',
                'message'=>'Something Went Wrong. Please Try Again',
                'data'=>[]
            ];
        }
    }

    public function updatemypreferences(Request $request){

        $request->validate([
            'from_height'=>'required',
            'to_height'=>'required',
            'from_age'=>'required|integer',
            'to_age'=>'required|integer',
            'from_distance'=>'required|integer',
            //'to_distance'=>'required|integer',
            //'pref_gender'=>'required|string',
        ]);

        //$from_height=round($request->from_height/30, 1);
        //$to_height=round($request->to_height/30, 1);

        $from_height=$request->from_height;
        $to_height=$request->to_height;

        $result=$request->user->update(array_merge($request->only('from_age', 'to_age','from_distance','to_distance','pref_gender'), ['from_height'=>$from_height,'to_height'=>$to_height]));
        if($result){
            return [
                'status'=>'success',
                'message'=>'My Preferences Has Been Updated',
                'data'=>[]
            ];
        }else{
            return [
                'status'=>'failed',
                'message'=>'Something Went Wrong. Please Try Again',
                'data'=>[]
            ];
        }
    }
    public function getmypreferences(Request $request){
        $user=$request->user;
        $myuserpref=array(
            'id'=>$user->id,
            //'height_feet'=>$user->from_height,
//            'from_height'=>round($user->from_height*30),
//            'to_height'=>round($user->to_height*20),
            'from_height'=>$user->from_height,
            'to_height'=>$user->to_height,
            'from_age'=>$user->from_age,
            'to_age'=>$user->to_age,
            'from_distance'=>$user->from_distance,
            //'to_distance'=>$user->to_distance,
            'pref_gender'=>$user->pref_gender,

        );

        if($myuserpref){
            return [
                'status'=>'success',
                'message'=>'My Preferences succes',
                'data'=>$myuserpref
            ];
        }else{
            return [
                'status'=>'failed',
                'message'=>'Something Went Wrong. Please Try Again',
                'data'=>[]
            ];
        }


    }

    public function getInterests(Request $request){
        $user=$request->user;
        $interests=Interest::get();
        $user_interests=$user->interests;

        return [
            'status'=>'success',
            'data'=>compact('interests','user_interests')
        ];

    }

    public function updateInterests(Request $request){

        $user=$request->user;

        $request->validate([
            'interests'=>'array']);

        $user->interests()->sync($request->interests);

        return [
            'status'=>'success'
        ];
    }

}
