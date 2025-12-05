<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Listing;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * GET /api/orders
     * Danh sách đơn hàng
     * 
     * Logic:
     * - Admin: Xem tất cả đơn hàng
     * - User thường: Xem đơn mình mua (buyer_id) + đơn mình bán (seller_id)
     * - Seller có thể vừa mua vừa bán, nên xem cả 2 loại
     */
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $query = Order::with(['buyer:id,full_name,email,phone', 'seller:id,full_name,email', 'shop:id,name,phone', 'listing:id,title,price']);

            // Admin xem tất cả, user thường chỉ xem đơn liên quan đến mình
            if ($user->role !== 'admin') {
                $query->where(function($q) use ($user) {
                    $q->where('buyer_id', $user->id)      // Đơn mình mua
                      ->orWhere('seller_id', $user->id);  // Đơn mình bán
                });
            }

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            // Sort
            $sortBy = $request->get('sort', 'created_at');
            $order = $request->get('order', 'desc');
            $query->orderBy($sortBy, $order);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $orders = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/orders/{id}
     * Chi tiết đơn hàng
     */
    public function show($id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::with([
                'buyer:id,full_name,email,phone',
                'seller:id,full_name,email',
                'shop:id,name,address,phone,email',
                'listing:id,title,description,price,images,shop_id'
            ])->find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            // Check permission
            if ($user->role !== 'admin' && $order->buyer_id != $user->id && $order->seller_id != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only view your own orders'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/orders
     * Tạo đơn hàng mới
     * 
     * Note: Mọi user đã đăng nhập đều có thể mua hàng (buyer, seller, admin)
     * Seller cũng có thể mua từ shop khác
     */
    public function store(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Mọi user đều có thể mua hàng, không giới hạn role

            $validator = Validator::make($request->all(), [
                'listing_id' => 'required|exists:listings,id',
                'quantity' => 'required|integer|min:1',
                'shipping_address' => 'required|array',
                'shipping_address.name' => 'required|string|max:100',
                'shipping_address.phone' => 'required|string|max:20',
                'shipping_address.address' => 'required|string|max:255',
                'shipping_address.district' => 'required|string|max:100',
                'shipping_address.city' => 'required|string|max:100',
                'shipping_address.ward' => 'nullable|string|max:100',
                'shipping_address.postal_code' => 'nullable|string|max:20',
                'payment_method' => 'required|in:cod,vnpay,momo,bank_transfer',
                'note' => 'nullable|string|max:500',
                'coupon_code' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            DB::beginTransaction();

            // Get listing
            $listing = Listing::with('shop')->find($request->listing_id);
            
            if (!$listing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Listing not found'
                ], 404);
            }

            // Không cho phép mua từ shop của chính mình
            $sellerId = $listing->user_id ?? ($listing->shop ? $listing->shop->owner_user_id : null);
            if ($sellerId && $sellerId == $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot buy from your own shop'
                ], 400);
            }

            // Check stock (if listing has stock field)
            if (isset($listing->stock) && $listing->stock < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not enough stock',
                    'requested' => $request->quantity,
                    'available' => $listing->stock
                ], 400);
            }

            // Calculate amounts
            $unitPrice = $listing->price;
            $totalAmount = $unitPrice * $request->quantity;
            $shippingFee = 0; // TODO: Calculate based on location
            $discountAmount = 0; // TODO: Apply coupon if provided
            $taxAmount = 0; // TODO: Calculate tax if needed
            $finalAmount = $totalAmount + $shippingFee + $taxAmount - $discountAmount;

            // Generate order number
            $orderNumber = Order::generateOrderNumber();

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'buyer_id' => $user->id,
                'seller_id' => $listing->user_id ?? $listing->shop->owner_user_id,
                'shop_id' => $listing->shop_id,
                'listing_id' => $listing->id,
                'quantity' => $request->quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => 'unpaid',
                'shipping_address' => $request->shipping_address,
                'note' => $request->note,
                'coupon_code' => $request->coupon_code,
            ]);

            // Update stock if available
            if (isset($listing->stock)) {
                $listing->decrement('stock', $request->quantity);
            }

            // Send notification to seller
            if ($order->seller_id) {
                Notification::create([
                    'user_id' => $order->seller_id,
                    'title' => 'Đơn hàng mới',
                    'message' => "Bạn có đơn hàng mới #{$order->order_number}",
                    'type' => 'order',
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'listing_id' => $order->listing_id,
                    'quantity' => $order->quantity,
                    'total_amount' => $order->total_amount,
                    'shipping_fee' => $order->shipping_fee,
                    'discount_amount' => $order->discount_amount,
                    'final_amount' => $order->final_amount,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/orders/{id}
     * Cập nhật đơn hàng (seller xác nhận, cập nhật tracking)
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            // Check permission - only seller/shop owner can update
            if ($user->role !== 'admin' && $order->seller_id != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only shop owner can update order status'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,processing,shipping,delivered,completed,cancelled',
                'tracking_number' => 'nullable|string|max:100',
                'note' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Validate status flow
            if (!$order->canUpdate()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update order that is completed, cancelled or refunded'
                ], 400);
            }

            DB::beginTransaction();

            $updateData = ['status' => $request->status];

            // Update timestamps based on status
            if ($request->status === 'shipping' && !$order->shipped_at) {
                $updateData['shipped_at'] = now();
            }

            if ($request->status === 'delivered' && !$order->delivered_at) {
                $updateData['delivered_at'] = now();
                $updateData['payment_status'] = 'paid'; // Auto mark as paid when delivered
            }

            if ($request->has('tracking_number')) {
                $updateData['tracking_number'] = $request->tracking_number;
            }

            $order->update($updateData);

            // Send notification to buyer
            Notification::create([
                'user_id' => $order->buyer_id,
                'title' => 'Cập nhật đơn hàng',
                'message' => "Đơn hàng #{$order->order_number} đã được cập nhật: " . $request->status,
                'type' => 'order',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order updated successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'tracking_number' => $order->tracking_number,
                    'updated_at' => $order->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/orders/{id}
     * Hủy đơn hàng
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            // Check permission
            $isBuyer = $order->buyer_id == $user->id;
            $isSeller = $order->seller_id == $user->id;
            $isAdmin = $user->role === 'admin';

            if (!$isBuyer && !$isSeller && !$isAdmin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to cancel this order'
                ], 403);
            }

            // Check if can cancel
            if (!$order->canCancel()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel order that is already shipped or delivered'
                ], 400);
            }

            DB::beginTransaction();

            $cancelReason = $request->input('cancel_reason', 'Cancelled by ' . ($isBuyer ? 'buyer' : 'seller'));

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $cancelReason,
            ]);

            // Restore stock if available
            if ($order->listing && isset($order->listing->stock)) {
                $order->listing->increment('stock', $order->quantity);
            }

            // Send notification
            $notifyUserId = $isBuyer ? $order->seller_id : $order->buyer_id;
            if ($notifyUserId) {
                Notification::create([
                    'user_id' => $notifyUserId,
                    'title' => 'Đơn hàng đã bị hủy',
                    'message' => "Đơn hàng #{$order->order_number} đã bị hủy. Lý do: {$cancelReason}",
                    'type' => 'order',
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order cancelled successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'cancel_reason' => $order->cancel_reason,
                    'cancelled_at' => $order->cancelled_at,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}