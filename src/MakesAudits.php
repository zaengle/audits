<?php

namespace Zaengle\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * Trait MakesAudits
 */
trait MakesAudits
{
    protected $columnName = 'audits';

    protected static function boot()
    {
        foreach (static::getAuditHooks() as $hook) {
            static::$hook(function (Model $model) {
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

    /**
     * @return array
     */
    private function auditData()
    {
        return $this->getAttributes();
    }

    public function trigger()
    {
        $this->persistAudit($this->auditData(), $this->setActingUser());
    }

    /**
     * @param array $data
     * @param $user
     */
    private function persistAudit(array $data = [], $user = null)
    {
        $audits = $this->attributes[$this->columnName] ?: [];

        $newAudit = [
            'updated_at' => now()->toDateTimeString(),
            'data' => $data,
        ];

        $newAudit['updated_by'] = $user ? $user->getKey() : null;

        array_push($audits, $newAudit);

        $dispatcher = self::getEventDispatcher();
        self::unsetEventDispatcher();

        $this->update([$this->columnName => $audits]);

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
        return $authenticatable['with_guard']
            ? auth()->guard(key($authenticatable))->user()
            : auth()->user();
    }
}
