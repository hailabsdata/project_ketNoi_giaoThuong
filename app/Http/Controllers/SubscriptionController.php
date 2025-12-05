<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * 1. POST /subscriptions - Đăng ký gói mới
     */
    public function subscribe(Request $request)
    {
        $user = $request->user('api');

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'required|in:vnpay,momo,bank_transfer',
            'duration_months' => 'nullable|integer|in:1,3,6,12',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user already has active subscription
        $activeSubscription = UserSubscription::where('user_id', $user->id)
            ->active()
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'message' => 'You already have an active subscription. Please cancel it first or wait until it expires.'
            ], 400);
        }

        $plan = SubscriptionPlan::active()->find($request->plan_id);
        if (!$plan) {
            return response()->json([
                'message' => 'Plan not available'
            ], 404);
        }

        $durationMonths = $request->duration_months ?? 1;

        // Calculate pricing with discount
        $pricing = $plan->calculatePrice($durationMonths);

        // TODO: Apply coupon if provided
        // For now, just use calculated pricing

        $startDate = now()->toDateString();
        $endDate = now()->addMonths($durationMonths)->toDateString();

        // Create subscription
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'duration_months' => $durationMonths,
            'price' => $pricing['base_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_amount' => $pricing['final_amount'],
            'payment_method' => $request->payment_method,
            'coupon_code' => $request->coupon_code,
            'status' => 'pending',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'started_at' => now(),
            'expires_at' => now()->addMonths($durationMonths),
            'is_active' => false,
        ]);

        // Generate payment URL (mock for now)
        $paymentUrl = $this->generatePaymentUrl($subscription, $request->payment_method);

        // Create notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Đăng ký gói thành viên',
            'message' => "Bạn đã đăng ký gói {$plan->name}. Vui lòng thanh toán để kích hoạt.",
            'data' => ['subscription_id' => $subscription->id],
            'icon' => 'credit-card',
            'priority' => 'high',
        ]);

        return response()->json([
            'message' => 'Subscription created successfully',
            'data' => [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                ],
                'duration_months' => $subscription->duration_months,
                'price' => $subscription->price,
                'discount_amount' => $subscription->discount_amount,
                'final_amount' => $subscription->final_amount,
                'status' => $subscription->status,
                'start_date' => $subscription->start_date,
                'end_date' => $subscription->end_date,
                'payment_method' => $subscription->payment_method,
                'payment_url' => $paymentUrl,
                'created_at' => $subscription->created_at,
            ]
        ], 201);
    }

    /**
     * 2. PUT /subscriptions/{id}/renew - Gia hạn gói
     */
    public function renew(Request $request, $id)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::where('user_id', $user->id)->find($id);
        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration_months' => 'nullable|integer|in:1,3,6,12',
            'payment_method' => 'required|in:vnpay,momo,bank_transfer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $plan = $subscription->plan;
        if (!$plan || !$plan->is_active) {
            return response()->json([
                'message' => 'Plan not available for renewal'
            ], 400);
        }

        $durationMonths = $request->duration_months ?? 1;

        // Calculate pricing with discount
        $pricing = $plan->calculatePrice($durationMonths);

        $oldEndDate = $subscription->end_date;
        
        // Calculate new end date from current end date or now (whichever is later)
        $baseDate = Carbon::parse($subscription->end_date)->isFuture() 
            ? Carbon::parse($subscription->end_date) 
            : Carbon::now();
        
        $newEndDate = $baseDate->addMonths($durationMonths)->toDateString();

        // Update subscription
        $subscription->update([
            'duration_months' => $durationMonths,
            'price' => $pricing['base_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_amount' => $pricing['final_amount'],
            'payment_method' => $request->payment_method,
            'end_date' => $newEndDate,
            'expires_at' => Carbon::parse($newEndDate),
            'status' => 'pending',
        ]);

        // Generate payment URL
        $paymentUrl = $this->generatePaymentUrl($subscription, $request->payment_method);

        // Create notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Gia hạn gói thành viên',
            'message' => "Bạn đã gia hạn gói {$plan->name}. Vui lòng thanh toán.",
            'data' => ['subscription_id' => $subscription->id],
            'icon' => 'refresh',
            'priority' => 'normal',
        ]);

        return response()->json([
            'message' => 'Subscription renewed successfully',
            'data' => [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'duration_months' => $subscription->duration_months,
                'price' => $subscription->price,
                'discount_amount' => $subscription->discount_amount,
                'final_amount' => $subscription->final_amount,
                'old_end_date' => $oldEndDate,
                'new_end_date' => $subscription->end_date,
                'payment_url' => $paymentUrl,
                'created_at' => $subscription->created_at,
            ]
        ]);
    }

    /**
     * 3. GET /subscriptions/current - Gói hiện tại
     */
    public function current(Request $request)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::with('plan')
            ->where('user_id', $user->id)
            ->active()
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 404);
        }

        return response()->json([
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id,
            'plan' => [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'slug' => $subscription->plan->slug,
                'price' => $subscription->plan->price,
                'features' => $subscription->plan->features,
            ],
            'status' => $subscription->status,
            'start_date' => $subscription->start_date,
            'end_date' => $subscription->end_date,
            'days_remaining' => $subscription->days_remaining,
            'usage' => $subscription->usage,
            'auto_renew' => $subscription->auto_renew,
            'created_at' => $subscription->created_at,
            'updated_at' => $subscription->updated_at,
        ]);
    }

    /**
     * 4. GET /subscriptions/history - Lịch sử
     */
    public function history(Request $request)
    {
        $user = $request->user('api');

        $query = UserSubscription::with('plan:id,name,price')
            ->where('user_id', $user->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'last_page' => $subscriptions->lastPage(),
            ],
        ]);
    }

    /**
     * 5. DELETE /subscriptions/{id}/cancel - Hủy gói
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->find($id);

        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found or already inactive'
            ], 404);
        }

        // Calculate refund
        $refundInfo = $subscription->calculateRefund();

        // Cancel subscription
        $subscription->cancel($request->reason);

        $plan = $subscription->plan;

        // Create notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Gói thành viên đã được hủy',
            'message' => $plan ? "Gói {$plan->name} của bạn đã được hủy." : 'Gói thành viên đã được hủy.',
            'data' => [
                'subscription_id' => $subscription->id,
                'refund_amount' => $refundInfo['refund_amount'],
            ],
            'icon' => 'x-circle',
            'priority' => 'normal',
        ]);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'data' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'cancelled_at' => $subscription->canceled_at,
                'refund_amount' => $refundInfo['refund_amount'],
                'refund_note' => $refundInfo['refund_note'],
            ]
        ]);
    }

    /**
     * Generate payment URL (mock)
     */
    private function generatePaymentUrl($subscription, $paymentMethod)
    {
        // TODO: Implement real payment gateway integration
        
        $baseUrls = [
            'vnpay' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'momo' => 'https://test-payment.momo.vn/gw_payment/transactionProcessor',
            'bank_transfer' => null,
        ];

        $baseUrl = $baseUrls[$paymentMethod] ?? null;

        if (!$baseUrl) {
            return null;
        }

        // Mock payment URL with basic params
        return $baseUrl . '?amount=' . ($subscription->final_amount * 100) 
            . '&orderInfo=Subscription-' . $subscription->id
            . '&returnUrl=' . url('/api/subscriptions/payment/callback');
    }
}
