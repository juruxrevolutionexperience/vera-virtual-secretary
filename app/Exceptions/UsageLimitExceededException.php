<?php

namespace App\Exceptions;

use Exception;
use App\Models\User;

class UsageLimitExceededException extends Exception
{
    protected User $user;
    protected int $limit;
    protected int $used;

    public function __construct(User $user, int $used, int $limit)
    {
        $this->user = $user;
        $this->limit = $limit;
        $this->used = $used;

        $message = "Has alcanzado el límite de tu plan {$user->plan_type}. " .
                   "Procesados: {$used}/{$limit} emails este mes.";

        parent::__construct($message, 429); // 429 Too Many Requests
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getUsed(): int
    {
        return $this->used;
    }

    public function getRemaining(): int
    {
        return max(0, $this->limit - $this->used);
    }

    /**
     * Render como respuesta JSON (para APIs)
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Usage limit exceeded',
                'message' => $this->getMessage(),
                'plan' => $this->user->plan_type,
                'limit' => $this->limit,
                'used' => $this->used,
                'remaining' => $this->getRemaining(),
                'upgrade_url' => route('settings.plans'),
            ], 429);
        }

        // Para requests web, redirigir a página de upgrade
        return redirect()
            ->route('settings.usage')
            ->with('error', $this->getMessage())
            ->with('upgrade_needed', true);
    }
}
