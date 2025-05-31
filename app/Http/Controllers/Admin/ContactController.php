<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * お問い合わせ一覧
     */
    public function index(Request $request)
    {
        $query = Contact::with('user')->latest();

        // フィルタリング
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate(20);

        $stats = [
            'total' => Contact::count(),
            'new' => Contact::where('status', 'new')->count(),
            'in_progress' => Contact::where('status', 'in_progress')->count(),
            'replied' => Contact::where('status', 'replied')->count(),
            'resolved' => Contact::where('status', 'resolved')->count(),
            'closed' => Contact::where('status', 'closed')->count(),
        ];

        return view('admin.contacts.index', compact('contacts', 'stats'));
    }

    /**
     * お問い合わせ詳細
     */
    public function show(Contact $contact)
    {
        $contact->load('user');
        return view('admin.contacts.show', compact('contact'));
    }

    /**
     * ステータス更新
     */
    public function updateStatus(Request $request, Contact $contact)
    {
        $validStatuses = implode(',', array_keys(Contact::getStatuses()));

        $request->validate([
            'status' => "required|in:{$validStatuses}",
            'admin_notes' => 'nullable|string|max:2000'
        ]);

        $contact->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'replied_at' => in_array($request->status, ['replied', 'resolved']) ? now() : $contact->replied_at,
        ]);

        return redirect()->back()->with('success', 'ステータスが更新されました。');
    }

    /**
     * 削除
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.contacts.index')->with('success', 'お問い合わせが削除されました。');
    }
}
