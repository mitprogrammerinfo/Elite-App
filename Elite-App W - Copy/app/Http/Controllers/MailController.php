<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;

class MailController extends Controller
{
    
    public function sendEmail()
{
    try {
      //  return response()->json(['message'=>'Email has been sent successfully!']);
        $to = "flutterbyirfangill51214@gmail.com";
        $msg = "Hello, this is a test email";
        $subject = "Test Email";

        Mail::to($to)->send(new VerificationCodeMail($msg, $subject));

        return response()->json(['message' => 'Email sent successfully']);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Email sending failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
