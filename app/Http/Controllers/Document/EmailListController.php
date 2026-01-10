<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\EmailList;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $emailLists = EmailList::query()
            ->orderBy('user_name')
            ->paginate(20);

        return view('document.email-list.index', [
            'emailLists' => $emailLists,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('document.email-list.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:email_lists,email'],
            'department' => ['required', 'string', 'max:255'],
        ]);

        EmailList::create($validated);

        return redirect()
            ->route('document.email-list.index')
            ->with('success', 'Email entry created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmailList $emailList)
    {
        return view('document.email-list.show', [
            'emailList' => $emailList,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailList $emailList)
    {
        return view('document.email-list.edit', [
            'emailList' => $emailList,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailList $emailList)
    {
        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('email_lists', 'email')->ignore($emailList->id),
            ],
            'department' => ['required', 'string', 'max:255'],
        ]);

        $emailList->update($validated);

        return redirect()
            ->route('document.email-list.index')
            ->with('success', 'Email entry updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailList $emailList)
    {
        $emailList->delete();

        return redirect()
            ->route('document.email-list.index')
            ->with('success', 'Email entry deleted.');
    }
}
