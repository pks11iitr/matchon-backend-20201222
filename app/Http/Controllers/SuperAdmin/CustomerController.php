<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Education;
use App\Models\Employment;
use App\Models\EthniCity;
use App\Models\Height;
use App\Models\Interest;
use App\Models\Ocupation;
use App\Models\Religion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\In;

class CustomerController extends Controller
{
    public function index(Request $request){

        //var_dump($request->all());die;
        $customers=Customer::where(function($customers) use($request){
            $customers->where('name','LIKE','%'.$request->search.'%')
                ->orWhere('mobile','LIKE','%'.$request->search.'%')
                ->orWhere('email','LIKE','%'.$request->search.'%');
        });

        if($request->fromdate)
            $customers=$customers->where('created_at', '>=', $request->fromdate.' 00:00:00');

        if($request->todate)
            $customers=$customers->where('created_at', '<=', $request->todate.' 23:59:50');

        if($request->status)
            $customers=$customers->where('status', $request->status);

        if($request->ordertype)
            $customers=$customers->orderBy('created_at', $request->ordertype);

        $customers=Customer::paginate(10);
        return view('admin.customer.view',['customers'=>$customers]);
    }

    public function details(Request $request,$id){
        $customers = Customer::with('Height','Ethnicity','Work','Job','gallery','Education')->findOrFail($id);
        return view('admin.customer.details',['customers'=>$customers]);
    }

      public function images(Request  $request,$id){
          $request->validate([
              'images'=>'required|array',
          ]);
             $customer=Customer::find($id);
          foreach($request->images as $image)
              $document=$customer->saveDocument($image,'profile');
          return redirect()->back()->with('success', 'Customer images uploaded Successfully');
      }

      public function deleteimage (Request $request,$id){
          Document::where('id', $id)->delete();
          return redirect()->back()->with('success', 'Images has been deleted');

      }

    public function create(Request $request){
        $height=Height::select('name', 'id')->get();
        $ethnicity=EthniCity::select('name', 'id')->get();
        $occupation=Ocupation::select('name', 'id')->get();
        $employment=Employment::select('name', 'id')->get();
        $education=Education::select('name', 'id')->get();
        $religion=Religion::select('name', 'id')->get();
        $countries= Country::select('name','id')->get();
        return view('admin.customer.add',['height'=>$height,'ethnicity'=>$ethnicity,'occupation'=>$occupation,'employment'=>$employment,'education'=>$education,'religion'=>$religion,'countries'=>$countries]);
    }

   public function store(Request $request)
   {
       $request->validate([
           'name' => 'required',
           'gender' => 'required',
           'dob' => 'required',
           'mobile' => 'required|unique:customers',
           'email' => 'required|unique:customers',
           'about_me' => 'required',
           'height_id' => 'required',
           'ethicity_id' => 'required',
           'education_id' => 'required',
           'occupation_id' => 'required',
           'religion_id' => 'required',
           'job_id' => 'required',
           'image' => 'image'
       ]);
         $country = Country::find($request->country);

       if ($customers = Customer::create($request->only('name', 'gender', 'dob', 'mobile', 'email', 'about_me', 'height_id', 'ethicity_id', 'education_id', 'occupation_id', 'job_id', 'religion_id', 'drinking', 'smoking', 'marijuana', 'drugs', 'age_show', 'distance_show', 'image', 'password','rate','account_type','address'))){
           $customers->country=$country->id??'';
           $customers->country_flag=$country->getRawOriginal('image')??'';
           $customers->password=Hash::make($request->password);
           $customers->save();
           $customers->saveImage($request->image, 'customers');

           if($request->account_type=='ADMIN'){
               $user=User::create([
                  'email'=>$request->email,
                   'name'=>$request->name,
                   'customer_id'=>$customers->id,
                   'password'=>Hash::make($request->password)
               ]);
               $user->assignRole('caller');
           }

           if(!$customers->sendbird_token){
               //register on sendbird app
               $sendbird=app('App\Services\SendBird\SendBird');
               $response=$sendbird->createUser($user);

               if(isset($response['user_id'])){
                   $customers->sendbird_token=$response['access_token']??null;
                   $customers->save();
               }else{
                   return redirect()->back()->with('error', 'Something went wrong at sendbird. Please try again');
               }
           }

           return redirect()->route('customer.list')->with('success', 'Customer has been created');
       }
       return redirect()->back()->with('error', 'Customer create failed');

   }

    public function edit(Request $request,$id){
        $customers = Customer::with('interests')->findOrFail($id);
        $height=Height::select('name', 'id')->get();
        $ethnicity=EthniCity::select('name', 'id')->get();
        $occupation=Ocupation::select('name', 'id')->get();
        $employment=Employment::select('name', 'id')->get();
        $education=Education::select('name', 'id')->get();
        $religion=Religion::select('name', 'id')->get();
        $countries= Country::select('name','id')->get();
        $interests=Interest::active()->get();

        return view('admin.customer.edit',['customers'=>$customers,'height'=>$height,'ethnicity'=>$ethnicity,'occupation'=>$occupation,'employment'=>$employment,'education'=>$education,'religion'=>$religion,'countries'=>$countries, 'interests'=>$interests]);
    }

    public function update(Request $request,$id){
        $request->validate([
            'name' => 'required',
            'gender' => 'required',
            'dob' => 'required',
            'mobile' => "required|unique:customers,mobile,$id",
            'email' => "required|unique:customers,email,$id",
            'about_me' => 'required',
            'height_id' => 'required',
            'ethicity_id' => 'required',
            'education_id' => 'required',
            'occupation_id' => 'required',
            'religion_id' => 'required',
            'job_id' => 'required',
            'image' => 'image',
//            'short_video'=>'mimes:mp4,mov,ogg,3gp'
        ]);
        $customers = Customer::findOrFail($id);
        $country = Country::find($request->country);

        if ($customers->update($request->only('name', 'gender', 'dob', 'mobile', 'email', 'about_me', 'height_id', 'ethicity_id', 'education_id', 'occupation_id', 'job_id', 'religion_id', 'drinking', 'smoking', 'marijuana', 'drugs', 'age_show', 'distance_show','rate','account_type','address', 'system_messages'))){

            $customers->interests()->sync($request->interests);

             $customers->country=$country->id??'';
             $customers->country_flag=$country->getRawOriginal('image')??'';

            if(isset($request->password))
            {
                $customers->password=Hash::make($request->password);

            }
            $customers->save();

            if($request->account_type=='ADMIN'){

                $user=User::where('customer_id',$customers->id)->first();

                if($user){
                    $user->update([
                        'email'=>$request->email,
                        'name'=>$request->name,
                        'password'=>Hash::make($request->password)
                    ]);
                }else{
                    $user=User::create([
                        'email'=>$request->email,
                        'name'=>$request->name,
                        'customer_id'=>$customers->id,
                        'password'=>Hash::make($request->password)
                    ]);
                    $user->assignRole('caller');
                }

                $user->assignRole('caller');
            }

            if($request->image){
                $customers->saveImage($request->image, 'customers');
            }

            if($request->short_video){
                $customers->saveShortVideo($request->short_video, 'customers');
            }


            if(!$customers->sendbird_token){
                //register on sendbird app
                $sendbird=app('App\Services\SendBird\SendBird');
                $response=$sendbird->createUser($user);

                if(isset($response['user_id'])){
                    $customers->sendbird_token=$response['access_token']??null;
                    $customers->save();
                }else{
                    return redirect()->back()->with('error', 'Something went wrong at sendbird. Please try again');
                }
            }

            return redirect()->route('customer.list')->with('success', 'Customer has been updated');
        }
        return redirect()->back()->with('error', 'Customer update failed');

    }

    public function image(Request $request){
            $document= Document::findOrFail($request->id);

            $customer = Customer::findOrFail($request->user_id);
            $customer->image=$document->getRawOriginal('file_path');

            $customer->save();

    }

    public function chat(Request $request,$id){
        $chats =Chat::findOrFail($id);
        return view('admin.customer.chat',['chats'=>$chats]);
    }

}
