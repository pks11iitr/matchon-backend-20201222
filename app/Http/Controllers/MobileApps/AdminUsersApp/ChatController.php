<?php

namespace App\Http\Controllers\MobileApps\AdminUsersApp;

use App\Http\Controllers\Controller;
use App\Jobs\SendBulkMessages;
use App\Models\Chat;
use App\Models\Customer;
use App\Services\Notification\FCMNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function chatlist(Request $request){

        $user=$request->user;

        $chatslist=Chat::with(['user1'=>function($user){
            $user->select('id', 'name', 'image');
        }, 'user2'=>function($user){
            $user->select('id', 'name', 'image');
        }])
            ->select(DB::raw('max(id) as id'), 'user_1', 'user_2')
            ->groupBy('user_1', 'user_2')
            ->where(function($query) use ($user){
                $query->where('user_1', $user->id)
                    ->orWhere('user_2', $user->id);
            })
            ->orderBy('id', 'desc')
            ->get();

        $chatids=[];
        foreach($chatslist as $chatid){
            $chatids[]=$chatid->id;
        }

        $chats=[];
        if(!empty($chatids)){
            $chatobj=Chat::whereIn('id', $chatids)
                ->get();
            foreach($chatobj as $chat){
                $chats[$chat->id]=$chat;
            }

        }


        $userchats=[];
        foreach($chatslist as $userchat){
            if($chats[$userchat->id]->user_1==$user->id){
                $userchats[]=[
                    'id'=>$userchat->user_2,
                    'name'=>$userchat->user2->name,
                    'image'=>$userchat->user2->image,
                    'chat'=>$chats[$userchat->id]->message,
                    'date'=>$chats[$userchat->id]->created_at
                ];
            }else{
                $userchats[]=[
                    'id'=>$userchat->user_1,
                    'name'=>$userchat->user1->name,
                    'image'=>$userchat->user1->image,
                    'chat'=>$chats[$userchat->id]->message,
                    'date'=>$chats[$userchat->id]->created_at
                ];
            }
        }



        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('userchats')
        ];


    }

    public function chatDetails(Request $request, $user_id){

        $user=$request->user;

        $chatsobj=Chat::with(['user1', 'user2'])
            ->where(function($query) use($user, $user_id){
                $query->where(function($query) use($user, $user_id){
                    $query->where('user_1', $user->id)
                        ->where('user_2', $user_id);
                })
                    ->orWhere(function($query) use($user, $user_id){
                        $query->where('user_1', $user_id)
                            ->where('user_2', $user->id);
                    });
            })
            ->orderBy('id','desc')
            ->paginate(100);

        $next_page_url=$chatsobj->nextPageUrl();
        $prev_page_url=$chatsobj->previousPageUrl();

        $chats=[];
        foreach ($chatsobj as $c){
            if(($c->user_1==$user->id && $c->direction==0)||($c->user_2==$user->id && $c->direction==1)){
                $chats[]=[
                    'user_id'=>$c->user2->id,
                    'user_image'=>$c->user2->image,
                    'image'=>$c->image,
                    'message'=>$c->message,
                    'date'=>$c->created_at,
                    'direction'=>0,
                ];
            }else{
                $chats[]=[
                    'user_id'=>$c->user1->id,
                    'user_image'=>$c->user1->image,
                    'image'=>$c->image,
                    'message'=>$c->message,
                    'date'=>$c->created_at,
                    'direction'=>1
                ];
            }
        }
        $chats=array_reverse($chats);
        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('chats', 'next_page_url', 'prev_page_url')
        ];

    }


    public function send(Request $request, $user_id){

        $request->validate([

            'type'=>'required|in:text,image',
            'message'=>'required_if:type,text',
            'image'=>'required_if:type,image',
        ]);

        $user=$request->user;

        $receiver=Customer::findOrFail($user_id);

        $chat=Chat::create([
            'user_1'=>($user->id < $user_id)?$user->id:$user_id,
            'user_2'=>($user->id < $user_id)?$user_id:$user->id,
            'direction'=>($user->id < $user_id)?0:1,
            'message'=>$request->message??'',
            'type'=>$request->type
        ]);

        switch($request->type){
            case 'image':
                $chat->saveImage($request->image, 'chats');
                break;
        }

        $receiver->notify(new FCMNotification('New Message from '.$user->name, $request->message??"[$request->type]", ['type'=>'chat','chat_id'=>$user->id.''], 'chat_screen'));
        return [
            'status'=>'success',
            'message'=>'',
            'data'=>[]
        ];

    }

    public function bulkMessage(Request $request){
        $request->validate([
            'type'=>'required|in:chat,call',
            'message_type'=>'required_if:type,chat|in:text,image',
            'message'=>'required_if:message_type,text|max:150',
            'image'=>'required_if:message_type,image'
        ]);

        $user=$request->user;

        if($request->image){
            $image=$request->image;
            $name = $image->getClientOriginalName();
            $contents = file_get_contents($image);
            $path = 'chats/' . $user->id . '/' . rand(111, 999) . '_' . str_replace(' ','_', $name);
            \Storage::put($path, $contents, 'public');
        }

        $this->dispatch(new SendBulkMessages($user, $request->type, $request->message_type,$request->message,$path??null));

        return [
            'status'=>'success',
            'message'=>'Message has been sent'
        ];

    }

}
