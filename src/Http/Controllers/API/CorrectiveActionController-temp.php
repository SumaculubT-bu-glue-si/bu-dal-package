    /**
    * Send reminders for specific corrective actions.
    */
    public function sendReminders(Request $request): JsonResponse
    {
    try {
    $actionIds = $request->input('action_ids', []);
    $notificationService = new \Bu\Server\Services\CorrectiveActionNotificationService();

    if (empty($actionIds)) {
    // If no specific actions are provided, send reminders for all overdue actions
    $result = $notificationService->sendOverdueReminders();
    } else {
    // Send reminders for specific actions
    $result = $notificationService->sendBulkNotifications($actionIds);
    }

    if ($result['success']) {
    return response()->json([
    'success' => true,
    'message' => $result['message'],
    'details' => $result
    ]);
    } else {
    return response()->json([
    'success' => false,
    'message' => $result['message']
    ], 500);
    }
    } catch (\Exception $e) {
    return response()->json([
    'success' => false,
    'message' => 'Failed to send reminders: ' . $e->getMessage()
    ], 500);
    }
    }