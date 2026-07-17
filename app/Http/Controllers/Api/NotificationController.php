<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->notifications();

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json($notification);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $notification->marquerCommeLue();

        return response()->json($notification);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->notificationsNonLues()->update([
            'statut' => 'lue',
            'date_lecture' => now(),
        ]);

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues']);
    }

    public function countUnread(Request $request)
    {
        $count = $request->user()->notificationsNonLues()->count();

        return response()->json(['count' => $count]);
    }

    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $notification->delete();

        return response()->json(null, 204);
    }
}
