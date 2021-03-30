<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\Customer;
use App\Models\User;
use App\Services\Notification\FCMNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $type,$user,$message_type,$message,$image;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $type,$message_type,$message,$image)
    {
        $this->user=$user;
        $this->type=$type;
        $this->message_type=$message_type;
        $this->message=$message;
        $this->image=$image;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->type=='call'){
            $this->sendCallInvites($this->user);
        }else if($this->type=='chat'){
            $this->sendChats($this->user, $this->message_type,$this->message,$this->image);
        }
    }


    private function sendCallInvites($user){

        $users=Customer::where('account_type', 'USER')->select('id','notification_token')->get();

        foreach($users as $u) {
            Chat::create([
                'user_1' => ($user->id < $u->id) ? $user->id : $u->id,
                'user_2' => ($user->id < $u->id) ? $u->id : $user->id,
                'direction' => ($user->id < $u->id) ? 0 : 1,
                'message' => $message ?? '',
                'type' => 'call'
            ]);

            $u->notify(new FCMNotification('Missed call from ' . $user->name, 'Missed call from ' . $user->name, ['type' => 'chat', 'chat_id' => $user->id . ''], 'chat_screen'));
        }
    }

    private function sendChats($user, $message_type, $message, $image){
        $users=Customer::where('account_type', 'USER')->select('id','notification_token')->get();

        if($message_type=='text'){
            foreach($users as $u){
                Chat::create([
                    'user_1'=>($user->id < $u->id)?$user->id:$u->id,
                    'user_2'=>($user->id < $u->id)?$u->id:$user->id,
                    'direction'=>($user->id < $u->id)?0:1,
                    'message'=>$message,
                    'type'=>'text'
                ]);

                $u->notify(new FCMNotification('New Message from '.$user->name, $message??"[$message_type]", ['type'=>'chat','chat_id'=>$user->id.''], 'chat_screen'));

            }
        }else if($message_type=='image'){

            foreach($users as $u){
                Chat::create([
                    'user_1'=>($user->id < $u->id)?$user->id:$u->id,
                    'user_2'=>($user->id < $u->id)?$u->id:$user->id,
                    'direction'=>($user->id < $u->id)?0:1,
                    'message'=>$message??'',
                    'image'=>$image,
                    'type'=>'image'
                ]);

                $u->notify(new FCMNotification('New Message from '.$user->name, $message??"[$message_type]", ['type'=>'chat','chat_id'=>$user->id.''], 'chat_screen'));

            }

        }


    }


}
