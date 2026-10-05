<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return back();
    }

    public function preferences(Request $request): RedirectResponse
    {
        $data = $request->validate(['notification_mode' => ['required', Rule::in(['digest', 'immediate'])]]);
        $request->user()->forceFill($data)->save();

        return back()->with('success', $data['notification_mode'] === 'digest' ? 'Napi összesítőt kapsz emailben.' : 'Minden értesítést azonnal megkapsz emailben.');
    }
}
