<?php

namespace Zaengle\Audit;

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
        $getActingUser = null;

        if (Config::get('audits.with_authenticated_user')) {
            if (count($authenticatable = Config::get('audits.auth_models')) == 1) {
                if (
                    $user = $authenticatable['with_guard']
                        ? auth()->guard(key($authenticatable))->user()
                        : auth()->user()
                ) {
                    $getActingUser = $user;
                }
            } elseif (count($authenticatables = Config::get('audits.auth_models')) > 1) {
                foreach ($authenticatables as $key => $authenticatable) {
                    if (
                        $user = $authenticatable['with_guard']
                            ? auth()->guard(key($authenticatable))->user()
                            : auth()->user()
                    ) {
                        $getActingUser = $user;
                    }
                }
            }
        }

        $this->persistAudit($this->auditData(), $getActingUser);
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
}
