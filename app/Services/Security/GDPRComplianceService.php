<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\DataExportRequest;
use App\Models\DataDeletionRequest;
use App\Models\Email;
use App\Models\Contact;
use App\Models\UserConsent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class GDPRComplianceService
{
    public function requestExport(User $user): DataExportRequest
    {
        $pending = DataExportRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])->first();

        if ($pending) return $pending;

        $request = DataExportRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_at' => now(),
            'expires_at' => now()->addDays(30),
            'token' => Str::random(64),
        ]);

        $this->processExport($request);
        return $request;
    }

    public function processExport(DataExportRequest $request): void
    {
        $request->markAsProcessing();

        try {
            $user = $request->user;
            $data = [
                'user' => $user->only(['id', 'name', 'email', 'created_at']),
                'emails' => Email::where('user_id', $user->id)->get()->toArray(),
                'contacts' => Contact::where('user_id', $user->id)->get()->toArray(),
            ];

            $json = json_encode($data, JSON_PRETTY_PRINT);
            $path = "exports/user_{$user->id}_" . time() . ".json";
            Storage::disk('local')->put($path, $json);

            $request->markAsCompleted($path, strlen($json));
        } catch (\Exception $e) {
            $request->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    public function requestDeletion(User $user, ?string $reason = null): DataDeletionRequest
    {
        $pending = DataDeletionRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])->first();

        if ($pending) return $pending;

        return DataDeletionRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => $reason,
            'requested_at' => now(),
            'requires_admin_approval' => true,
        ]);
    }

    public function processDeletion(DataDeletionRequest $request): void
    {
        if (!$request->isApproved()) {
            throw new \Exception('Requiere aprobación');
        }

        $request->markAsProcessing();

        try {
            $user = $request->user;

            Email::where('user_id', $user->id)->delete();
            Contact::where('user_id', $user->id)->delete();
            UserConsent::where('user_id', $user->id)->delete();

            $user->delete();
            $request->markAsCompleted();
        } catch (\Exception $e) {
            $request->markAsFailed($e->getMessage());
            throw $e;
        }
    }
}
