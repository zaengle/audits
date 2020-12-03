<?php

namespace Zaengle\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * Trait MakesAudits
 */
trait MakesAudits
{
    protected $auditableColumn = 'audits';

    protected static function bootMakesAudits()
    {
        foreach (static::getAuditHooks() as $hook) {
            static::$hook(function ($model) {
                $model->trigger();
            });
        }
    }

    /**
     * @return array
     */
    protected static function getAuditHooks()
    {
        return [
            'created',
            'updated',
        ];
    }

    public function trigger()
    {
        $this->persistAudit($this->auditData(), $this->setActingUser());
    }

    /**
     * @return array
     */
    private function auditData()
    {
        return $this->getAttributes();
    }

    /**
     * @param array $data
     * @param $user
     */
    private function persistAudit(array $data = [], $user = null)
    {
        unset($data[$this->auditableColumn]);

        $audits = json_decode(Arr::get($this->attributes, $this->auditableColumn, null), true) ?: [];

        $newAudit = [
            'updated_at' => now()->toDateTimeString(),
            'data' => $data,
        ];

        if (!! $user) {
            $newAudit['updated_by'] = $user->getKey();
        }

        array_push($audits, $newAudit);

        $dispatcher = self::getEventDispatcher();
        self::unsetEventDispatcher();

        $this->update([$this->auditableColumn => $audits]);

        self::setEventDispatcher($dispatcher);
    }

    /**
     * @return Authenticatable|null
     */
    private function setActingUser()
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

    /**
     * @param array $authenticatable
     * @return Authenticatable|null
     */
    private function getActingUser(array $authenticatable)
    {
        return Arr::get($authenticatable, 'guard', null)
            ? auth()->guard($authenticatable['guard'])->user()
            : auth()->user();
    }
}
