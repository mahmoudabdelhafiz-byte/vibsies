<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
class CreatorVerificationRenewal extends Notification {
 use Queueable;
 public function __construct(public string $verificationId,public string $verificationType,public string $expiresOn){}
 public function via(object $notifiable):array {return ['database'];}
 public function toArray(object $notifiable):array {
  return ['event'=>'verification_renewal','verification_id'=>$this->verificationId,
   'verification_type'=>$this->verificationType,'expires_on'=>$this->expiresOn];
 }
}
