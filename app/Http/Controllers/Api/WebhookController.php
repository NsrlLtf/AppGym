<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use App\Services\WebhookService;
use Carbon\Carbon;


class WebhookController extends Controller
{
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function index()
    {
        $webhooks = WebhookEndpoint::all();
        return response()->json(['webhooks' => $webhooks]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'events' => 'required|array',
            'secret' => 'required|string|min:16'
        ]);

        $webhook = WebhookEndpoint::create($validated);
        return response()->json(['webhook' => $webhook], 201);
    }

    public function show(WebhookEndpoint $webhook)
    {
        return response()->json(['webhook' => $webhook]);
    }

    public function update(Request $request, WebhookEndpoint $webhook)
    {
        $validated = $request->validate([
            'url' => 'url',
            'events' => 'array',
            'is_active' => 'boolean'
        ]);

        $webhook->update($validated);
        return response()->json(['webhook' => $webhook]);
    }

    public function destroy(WebhookEndpoint $webhook)
    {
        $webhook->delete();
        return response()->json(['message' => 'Webhook deleted']);
    }

    public function logs()
    {
        $logs = WebhookLog::with('endpoint')
                         ->latest()
                         ->paginate(20);
        return response()->json(['logs' => $logs]);
    }

    public function webhookLogs($webhookId)
    {
        $logs = WebhookLog::where('webhook_endpoint_id', $webhookId)
                         ->latest()
                         ->paginate(20);
        return response()->json(['logs' => $logs]);
    }

    public function test(WebhookEndpoint $webhook)
    {
        $this->webhookService->sendNotification('test.event', [
            'message' => 'This is a test event',
            'timestamp' => now()->timestamp
        ]);

        return response()->json(['message' => 'Test webhook sent']);
    }
}