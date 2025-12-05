<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\ChatMessage;
use App\Models\User;

/**
 * BA 3.4 — API Chat / Liên hệ
 *
 * Đơn giản hoá: chat 1-1 giữa 2 user, có thể gắn với 1 listing.
 *
 * - GET  /api/chat/conversations
 * - GET  /api/chat/messages/{user_id}
 * - POST /api/chat/messages
 * - PUT  /api/chat/messages/{user_id}/read
 */
class ChatController extends BaseApiController
{
    /**
     * GET /api/chat/conversations
     * Lấy danh sách cuộc trò chuyện
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        
        // Lấy danh sách user đã chat
        $conversations = ChatMessage::query()
            ->where(function ($q) use ($user) {
                $q->where('from_user_id', $user->id)
                  ->orWhere('to_user_id', $user->id);
            })
            ->with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($user) {
                return $message->from_user_id == $user->id 
                    ? $message->to_user_id 
                    : $message->from_user_id;
            })
            ->map(function ($messages, $userId) use ($user) {
                $lastMessage = $messages->first();
                $otherUser = $lastMessage->from_user_id == $user->id 
                    ? $lastMessage->toUser 
                    : $lastMessage->fromUser;
                
                return [
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'avatar' => $otherUser->avatar ?? null,
                    ],
                    'last_message' => [
                        'body' => $lastMessage->body,
                        'created_at' => $lastMessage->created_at,
                    ],
                    'unread_count' => $messages->where('to_user_id', $user->id)
                        ->where('is_read', false)
                        ->count(),
                ];
            })
            ->values();

        return $this->ok($conversations);
    }

    /**
     * GET /api/chat/messages/{user_id}
     * Lấy lịch sử tin nhắn với một user
     */
    public function messages(Request $request, int $userId)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 50);

        $messages = ChatMessage::query()
            ->where(function ($q) use ($user, $userId) {
                $q->where('from_user_id', $user->id)
                  ->where('to_user_id', $userId);
            })->orWhere(function ($q) use ($user, $userId) {
                $q->where('from_user_id', $userId)
                  ->where('to_user_id', $user->id);
            })
            ->with(['fromUser', 'toUser', 'listing'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        return $this->paginate($messages);
    }

    /**
     * POST /api/chat/messages
     * Gửi tin nhắn mới
     */
    public function send(Request $request)
    {
        $user = $request->user();
        $v = $request->validate([
            'to_user_id' => 'required|integer|exists:users,id',
            'listing_id' => 'nullable|integer|exists:listings,id',
            'body'       => 'required|string|max:2000',
        ]);

        $message = ChatMessage::create([
            'from_user_id' => $user->id,
            'to_user_id'   => $v['to_user_id'],
            'listing_id'   => $v['listing_id'] ?? null,
            'body'         => $v['body'],
        ]);

        return $this->created($message->load(['fromUser', 'toUser']));
    }

    /**
     * PUT /api/chat/messages/{user_id}/read
     * Đánh dấu tất cả tin nhắn từ user này là đã đọc
     */
    public function markAsRead(Request $request, int $userId)
    {
        $user = $request->user();
        
        ChatMessage::where('from_user_id', $userId)
            ->where('to_user_id', $user->id)
            ->update(['is_read' => true]);

        return $this->ok(['message' => 'Đã đánh dấu đã đọc']);
    }
}
