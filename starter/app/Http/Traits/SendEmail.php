<?php


namespace App\Http\Traits;


use App\Jobs\EmailAuctionJob;
use App\Jobs\EmailJob;
use Illuminate\Support\Facades\Mail;

trait SendEmail
{
    protected function send_EmailFun($email = null,$data = null,$subject = null,$type = 'otp'){
        EmailJob::dispatch($email,$data,$subject,$type)->onQueue('emails');
    }
    protected function send_auction_email($id,$email = null){
        EmailAuctionJob::dispatch($id,$email)->onQueue('emails');
    }
}
