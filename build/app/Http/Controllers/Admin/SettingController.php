<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\WaTemplate;
use App\Models\ScheduledTask;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $emailTemplates = EmailTemplate::all();
        $waTemplates = WaTemplate::all();
        $scheduledTasks = ScheduledTask::all();

        return view('admin.settings', compact('emailTemplates', 'waTemplates', 'scheduledTasks'));
    }

    public function updateEmailTemplate(Request $request, EmailTemplate $template)
    {
        $validated = $request->validate([
            'subject' => 'required|string',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('admin.settings')->with('success', 'Email template berhasil diupdate.');
    }

    public function updateWaTemplate(Request $request, WaTemplate $template)
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('admin.settings')->with('success', 'WA template berhasil diupdate.');
    }
}
