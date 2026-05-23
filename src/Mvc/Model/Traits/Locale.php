<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Mvc\Model\Traits;

use Phalcon\Mvc\Model\Exception as ModelException;
use Phalcon\Translate\Adapter\AbstractAdapter;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Locale as LocaleService;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEntity;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInjectable;

/**
 * Adds locale-aware field and method fallbacks to models.
 *
 * The trait lets consumers read/write logical fields such as `name` while the
 * model stores locale-specific columns like `nameEn` or `nameFr`. The current
 * locale is resolved from the model DI, so applications can switch locale
 * services per request while keeping the model code generic.
 */
trait Locale
{
    use AbstractInjectable;
    use AbstractEntity;

    /**
     * Translate a key through the model's translate service.
     *
     * This is a model-level convenience wrapper around Phalcon's translate
     * adapter. It keeps model validation messages and computed labels aligned
     * with the same `translate` service used by the rest of the application.
     *
     * @param string $translateKey Translation key to resolve.
     * @param array<array-key, mixed> $placeholders Placeholder values passed to
     *     the adapter.
     * @return string Translated string returned by the adapter.
     * @throws ServiceException When the translate service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function _(string $translateKey, array $placeholders = []): string
    {
        $translate = $this->getTypedService('translate', AbstractAdapter::class, 'model locale helpers');

        return $translate->_($translateKey, $placeholders);
    }

    /**
     * Dispatch missing method calls to locale-suffixed methods when available.
     *
     * For example, with locale `fr`, a call to `label()` will try `labelFr()`
     * before delegating to the parent model magic handler. This is intended for
     * computed localized accessors, not for replacing explicit public methods.
     *
     * @param string $method Missing method name.
     * @param array<array-key, mixed> $arguments Arguments forwarded to the
     *     localized method or parent handler.
     * @return mixed Localized method result, or the parent magic-call result.
     * @throws ModelException When the parent Phalcon model magic handler
     *     rejects the missing method.
     * @throws ServiceException When the locale service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function __call(string $method, array $arguments): mixed
    {
        $locale = $this->getTypedService('locale', LocaleService::class, 'model locale helpers');

        $lang = $locale->getLocale();

        if (!empty($lang)) {
            $call = $method . ucfirst($lang);
            if (method_exists($this, $call)) {
                return $this->$call(...$arguments);
            }
        }

//        return $this->$method(...$arguments);
        return parent::__call($method, $arguments);
    }

    /**
     * Write missing logical properties to locale-suffixed model fields.
     *
     * For example, with locale `en`, assigning `$model->name = 'Value'` writes
     * to `nameEn` when that property exists on the model. If no localized field
     * exists, the assignment is delegated to the parent Phalcon model handler.
     *
     * @param string $property Logical property name requested by the caller.
     * @param mixed $value Value to write.
     * @throws ServiceException When the locale service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function __set(string $property, mixed $value): void
    {
        $locale = $this->getTypedService('locale', LocaleService::class, 'model locale helpers');

        $lang = $locale->getLocale();

        if (!empty($lang)) {
            $set = $property . ucfirst($lang);

            if (property_exists($this, $set)) {
                $this->writeAttribute($set, $value);
                return;
            }
        }

        parent::__set($property, $value);
    }

    /**
     * Read missing logical properties from locale-suffixed model fields.
     *
     * For example, with locale `en`, reading `$model->name` returns `nameEn`
     * when that property exists on the model. If no localized field exists, the
     * lookup is delegated to the parent Phalcon model handler.
     *
     * @param string $property Logical property name requested by the caller.
     * @return mixed Localized field value or the parent magic-get result.
     * @throws ServiceException When the locale service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function __get(string $property): mixed
    {
        $locale = $this->getTypedService('locale', LocaleService::class, 'model locale helpers');

        $lang = $locale->getLocale();

        if (!empty($lang)) {
            $get = $property . ucfirst($lang);

            if (property_exists($this, $get)) {
                return $this->readAttribute($get);
            }
        }

        return parent::__get($property);
    }
}
