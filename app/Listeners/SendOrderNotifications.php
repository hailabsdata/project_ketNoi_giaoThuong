<?php
// app/Listeners/SendOrderNotifications.php
namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Notifications\ShipmentCreatedNotification;
use App\Services\Notification\NotificationBridge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderNotifications implements ShouldQueue
{
    public function __construct(private NotificationBridge $bridge) {}

    public function handle($event): void
    {
        // Khôi phục correlation context từ event
        $context = $event->getTraceContext();
        
        Log::info('SendOrderNotifications Listener Started', $context);
        
        try {
            switch (true) {
                case $event instanceof PaymentSucceeded:
                    $this->handlePaymentSucceeded($event, $context);
                    break;

                case $event instanceof ShipmentCreated:
                    $this->handleShipmentCreated($event, $context);
                    break;

                case $event instanceof OrderCreated:
                    $this->handleOrderCreated($event, $context);
                    break;
            }
            
            Log::info('SendOrderNotifications Listener Completed', $context);
            
        } catch (\Exception $e) {
            Log::error('SendOrderNotifications Listener Failed', array_merge(
                $context,
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            ));
            throw $e;
        }
    }
    
    private function handlePaymentSucceeded($event, array $context): void
    {
        // TODO: Get buyer from order
        // $buyer = User::find(...);
        // $this->bridge->to($buyer, new OrderStatusChanged(...));
        
        Log::info('Payment notification sent', $context);
    }
    
    private function handleShipmentCreated($event, array $context): void
    {
        // TODO: Get buyer from order
        // $buyer = User::find(...);
        // $this->bridge->to($buyer, new ShipmentCreatedNotification(...));
        
        Log::info('Shipment notification sent', $context);
    }
    
    private function handleOrderCreated($event, array $context): void
    {
        // TODO: Send order created notification
        Log::info('Order created notification sent', $context);
    }
}

// app/Listeners/UpdateReports.php
namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Jobs\IncrementReportCounters;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdateReports implements ShouldQueue
{
    public function handle(OrderCompleted $event): void
    {
        $context = $event->getTraceContext();
        
        Log::info('UpdateReports Listener Started', $context);
        
        try {
            // Dispatch Job với correlation ID
            IncrementReportCounters::dispatch(
                orderId: $event->orderId,
                amount: $event->total
            );
            
            Log::info('IncrementReportCounters Job Dispatched', $context);
            
        } catch (\Exception $e) {
            Log::error('UpdateReports Listener Failed', array_merge(
                $context,
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            ));
            throw $e;
        }
    }
}