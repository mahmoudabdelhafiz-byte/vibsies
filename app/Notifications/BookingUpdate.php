<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
class BookingUpdate extends Notification {
 use Queueable;
 public function __construct(public string $bookingId,public string $event){}
 public function via(object $notifiable):array {return ['database'];}
 public function toArray(object $notifiable):array {
  return ['booking_id'=>$this->bookingId,'event'=>$this->event];
 }
}
