<?php

namespace Modules\Communication\Services\Fcm;

use Illuminate\Collections\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;

class FcmService
{
    public function __construct(
        protected readonly Messaging $messaging,
        protected readonly FcmTokenService $tokenService,
    ) {
    }

    public function sendToUser(User $user, FcmPayload $payload): FcmResult
    {
        $tokens = $user->routeNotificationForFcm();

        if (empty($tokens)) {
            return new FcmResult(0, 0, []);
        }

        return $this->sendToTokens($tokens, $payload);
    }

    public function sendToMultipleUsers(Collection $users, FcmPayload $payload): array
    {
        $results = [];

        foreach ($users as $user) {
            $results[] = $this->sendToUser($user, $payload);
        }

        return $results;
    }

    public function sendToTopic(string $topic, FcmPayload $payload): void
    {
        $topicMessage = $this->messaging->createTopicMessage();

        $topicMessage-> jsonSerialize()['message'] = array_merge(
            $payload->toFcmMessage(),
            ['topic' => $topic]
        );

        if (App::hasDebugModeEnabled()) {
            Log::info("FCM topic message prepared", ['topic' => $topic, 'payload' => $payload->toArray()]);
        }
    }

    protected function sendToTokens(array $tokens, FcmPayload $payload): FcmResult
    {
        if (empty($tokens)) {
            return new FcmResult();
        }

        try {
            $multicast = $this->messaging->createMulticast();

            foreach ($tokens as $token) {
                $multicast->addRecipientWithToken($token);
            }

            $notification = Notification::fromArray($payload->toArray());

            $report = $this->messaging->sendMulticast($multicast, $notification);

            return $this->handleReport($report, $tokens);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('FCM send failed', ['error' => $e->getMessage()]);

            return new FcmResult(0, count($tokens), $tokens);
        }
    }

    protected function handleReport(MulticastSendReport $report, array $tokens): FcmResult
    {
        $successCount = $report->successes()->count();
        $failureCount = $report->failures()->count();
        $failedTokens = [];

        foreach ($report->failures()->items() as $index => $error) {
            $token = $tokens[$error['index']] ?? null;

            if ($token) {
                $failedTokens[] = $token;

                if ($this->isInvalidTokenError($error['error'] ?? '')) {
                    $this->tokenService->removeInvalidToken($token);
                }
            }
        }

        return new FcmResult($successCount, $failureCount, $failedTokens);
    }

    protected function isInvalidTokenError(string $error): bool
    {
        return str_contains($error, 'NOT_FOUND')
            || str_contains($error, 'INVALID_ARGUMENT')
            || str_contains($error, 'UNREGISTERED');
    }
}