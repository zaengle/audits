<?php

namespace Zaengle\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

trait MakesAudits
{
    protected string $auditableColumn = 'audits';

    public function initializeMakesAudits(): void
    {
        if (Config::get('audits.fillable_by_default')) {
            if (! isset($this->casts[$this->auditableColumn])) {
                $this->casts[$this->auditableColumn] = 'array';
            }

            if (! isset($this->fillable) || ! in_array($this->auditableColumn, $this->fillable, true)) {
                $this->fillable[] = $this->auditableColumn;
            }
        }
    }

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
        $data = $this->auditData();

        if (! $this->shouldTriggerAudit($data)) {
            return;
        }

        $this->persistAudit($data, $this->setActingUser());
    }

    public function manualAudit(array $auditData): void
    {
        self::withoutEvents(function () use ($auditData) {
            $column = $this->auditableColumn;

            $this->forceFill([
                $this->auditableColumn => array_merge($this->$column ?? [], [
                    $auditData
                ]),
            ]);

            $this->save();
        });
    }

    protected function shouldTriggerAudit(array $data): bool
    {
        return true;
    }

    protected function auditData(): array
    {
        return $this->getAttributes();
    }

    private function persistAudit(array $data = [], $user = null): void
    {
        unset($data[$this->auditableColumn]);

        $audits = json_decode(Arr::get($this->attributes, $this->auditableColumn, '[]'), true) ?: [];

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
