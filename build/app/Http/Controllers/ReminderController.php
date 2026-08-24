<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\WaTemplate;
use App\Models\Invoice;
use App\Models\CollectionAction;
use App\Models\ScheduledTask;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index()
    {
        $overdueInvoices = Invoice::overdue()->with('customer')->get();
        $emailTemplates = EmailTemplate::where('is_active', true)->get();
        $waTemplates = WaTemplate::where('is_active', true)->get();

        return view('reminders.index', compact('overdueInvoices', 'emailTemplates', 'waTemplates'));
    }

    public function sendBatch(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'required|array',
            'channel' => 'required|in:email,whatsapp,both',
            'template_id' => 'required|integer',
        ]);

        $invoices = Invoice::whereIn('id', $request->invoice_ids)->with('customer')->get();
        $sentCount = 0;

        foreach ($invoices as $invoice) {
            // Send reminder logic here
            CollectionAction::create([
                'invoice_id' => $invoice->id,
                'action_type' => $request->channel === 'whatsapp' ? 'WA' : 'EMAIL',
                'description' => "Reminder sent via {$request->channel}",
                'action_by' => auth()->user()->name,
                'action_date' => now(),
            ]);
            $sentCount++;
        }

        return redirect()->route('reminders.index')->with('success', "{$sentCount} reminder berhasil dikirim.");
    }

    public function log()
    {
        $actions = CollectionAction::with('invoice.customer')
            ->whereIn('action_type', ['EMAIL', 'WA'])
            ->latest('action_date')
            ->paginate(20);

        return view('reminders.log', compact('actions'));
    }

    public function config()
    {
        $tasks = ScheduledTask::where('task_type', 'reminder')->get();
        return view('reminders.config', compact('tasks'));
    }

    public function updateConfig(Request $request)
    {
        $request->validate([
            'schedule' => 'required|string',
            'is_active' => 'boolean',
        ]);

        ScheduledTask::where('task_type', 'reminder')->update([
            'schedule' => $request->schedule,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('reminders.config')->with('success', 'Konfigurasi berhasil diupdate.');
    }
}
