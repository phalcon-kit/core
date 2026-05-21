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

use Phalcon\Mvc\Model\Exception;
use Phalcon\Translate\Adapter\AbstractAdapter;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEntity;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInjectable;

/**
 * This trait provides functionality to handle localization in models.
 */
trait Locale
{
    use AbstractInjectable;
    use AbstractEntity;

    /**
     * Translate a given key using the translation service
     *
     * @param string $translateKey The key to be translated
     * @param array $placeholders The placeholders to be replaced in the translation
     * @return string The translated string
     */
    public function _(string $translateKey, array $placeholders = []): string
    {
        $translate = $this->getDI()->get('translate');
        assert($translate instanceof AbstractAdapter);

        return $translate->_($translateKey, $placeholders);
    }

    /**
     * Magic method to dynamically call localed named methods using the current locale
     * - Allow to call $this->methodName{Fr|En|Sp|...}() from missing methodName method
     *
     * @param string $method method name
     * @param array $arguments method arguments
     * @return mixed|null
     * @throws Exception
     */
    public function __call(string $method, array $arguments): mixed
    {
        $locale = $this->getDI()->get('locale');
        assert($locale instanceof \PhalconKit\Locale);

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
     * Magic setter to set localed named field automatically using the current locale
     * - Allow to set $this->name{Fr|En|Sp|...} for missing name property
     *
     * @param string $property property name
     * @param mixed $value value to be set for the property
     * @return void
     */
    public function __set(string $property, mixed $value): void
    {
        $locale = $this->getDI()->get('locale');
        assert($locale instanceof \PhalconKit\Locale);

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
     * Magic getter to get localed named field automatically using the current locale
     * - Allow to get $this->name{Fr|En|Sp|...} from missing name property
     *
     * @param string $property property name
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        $locale = $this->getDI()->get('locale');
        assert($locale instanceof \PhalconKit\Locale);

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
