<?php

namespace Zaengle\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

trait MakesAudits
{
    protected string $auditableColumn = 'audits';

    protected static function bootMakesAudits(): void
    {
        foreach (static::getAuditHooks() as $hook) {
            static::$hook(function ($model) {
                $model->trigger();
            });
        }
    }

    protected static function getAuditHooks(): array
    {
        return [
            'created',
            'updated',
        ];
    }

    public function trigger(): void
    {
        $this->persistAudit($this->auditData(), $this->setActingUser());
    }

    protected function auditData(): array
    {
        return $this->getAttributes();
    }

    private function persistAudit(array $data = [], $user = null): void
    {
        unset($data[$this->auditableColumn]);

        $audits = json_decode(Arr::get($this->attributes, $this->auditableColumn, null), true) ?: [];

        $newAudit = [
            'updated_at' => now()->toDateTimeString(),
            'data' => $data,
        ];

        if (!! $user) {
            $newAudit['updated_by'] = [
                'id' => $user->getKey(),
                'type' => get_class($user),
            ];
        }

        $audits[] = $newAudit;

        $dispatcher = self::getEventDispatcher();
        self::unsetEventDispatcher();

        $this->update([$this->auditableColumn => $audits]);

        self::setEventDispatcher($dispatcher);
    }

    private function setActingUser(): ?Authenticatable
    {
        $actingUser = null;

        if (Config::get('audits.with_authenticated_user')) {
            if (count($authenticatable = Config::get('audits.auth_models')) == 1) {
                if ($user = $this->getActingUser($authenticatable)) {
                    $actingUser = $user;
                }
            } elseif (count($authenticatables = Config::get('audits.auth_models')) > 1) {
                foreach ($authenticatables as $key => $authenticatable) {
                    if ($user = $this->getActingUser($authenticatable)) {
                        $actingUser = $user;
                    }
                }
            }
        }

        return $actingUser;
    }

    private function getActingUser(array $authenticatable): ?Authenticatable
    {
        return Arr::get($authenticatable, 'guard', null)
            ? auth()->guard($authenticatable['guard'])->user()
            : auth()->user();
    }
}
