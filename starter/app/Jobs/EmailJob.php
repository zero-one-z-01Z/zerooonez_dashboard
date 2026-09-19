<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $email,$data,$subject,$type;
    public function __construct($email = null,$data = null,$subject = null,$type = 'otp')
    {
        $this->email=$email;
        $this->data=$data;
        $this->subject=$subject;
        $this->type=$type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->email){
            try {

                $contact_company=' '.env('APP_NAME').' ';
                Mail::send([
                    'html' => $this->type=="otp"?'Email.email-otp':($this->type=="notifications"?"Email.email-notification":$this->type),],
                    ['data' => $this->data,'email'=>$this->email,'title'=>$contact_company],
                    function($message) use ($contact_company)
                    {
                        $message->to($this->email,$contact_company)->from(env('MAIL_FROM_ADDRESS'),$contact_company)->subject($this->subject);
                    }
                );

            } catch (\Exception $e) {
            }
        }
    }
}
