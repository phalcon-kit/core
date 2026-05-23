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

namespace PhalconKit;

use Phalcon\Assets\Exception;
use Phalcon\Tag as PhalconTag;
use Phalcon\Html\Escaper\EscaperInterface;
use PhalconKit\Assets\Manager;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Html\Escaper\EscaperInterface as PhalconKitEscaperInterface;

/**
 * This file is part of the Phalcon Kit.
 *
 * Represents a class that extends the Phalcon\Tag class and provides additional functionality.
 * @see PhalconTag
 */
class Tag extends PhalconTag
{
    /**
     * Represents an array that stores meta-information.
     * @var array $meta
     */
    protected static array $meta = [];
    
    /**
     * Represents an array that stores link information.
     * @var array $link
     */
    protected static array $link = [];
    
    /**
     * Represents an array that stores attributes.
     * @var array $attr
     */
    protected static array $attr = [];
    
    /**
     * Represents a service for managing assets.
     * @var Manager|null $assetsManager
     */
    protected static ?Manager $assetsManager = null;
    
    /**
     * Retrieves the instance of the Assets Manager.
     *
     * @return Manager The instance of the Assets Manager
     * @throws ServiceException When the tag DI cannot resolve a compatible
     *     assets manager.
     */
    public static function getAssetsManager(): Manager
    {
        if (self::$assetsManager instanceof Manager) {
            return self::$assetsManager;
        }

        try {
            $di = self::getDI();
        }
        catch (\Throwable $e) {
            throw new ServiceException(sprintf(
                'Could not resolve DI service "%s" for PhalconKit tag helpers because no tag DI is available.',
                'assets'
            ), previous: $e);
        }

        return self::$assetsManager = ServiceResolver::fromContainer(
            $di,
            'assets',
            Manager::class,
            context: 'PhalconKit tag helpers'
        );
    }
    
    /**
     * Sets the assets manager to be used.
     * @param Manager|null $assetsManager The assets manager to be set. Pass null to unset the current assets manager.
     * @return void
     */
    public static function setAssetsManager(?Manager $assetsManager): void
    {
        self::$assetsManager = $assetsManager;
    }
    
    /**
     * Retrieves the configured escaper instance.
     *
     * @param array $params The parameters used to retrieve the escaper instance.
     *                      The params should be an associative array with optional keys to configure the escaper.
     * @return EscaperInterface|null The configured escaper instance, or null if no escaper is found.
     * @see PhalconTag::getEscaper()
     */
    #[\Override]
    public static function getEscaper(array $params): ?EscaperInterface
    {
        return @PhalconTag::getEscaper($params);
    }
    
    /**
     * Retrieves the escaper service.
     *
     * @return EscaperInterface The instance of the escaper service.
     * @throws ServiceException When the default DI cannot resolve a native
     *     Phalcon escaper service.
     * @see PhalconTag::getEscaperService()
     */
    #[\Override]
    public static function getEscaperService(): EscaperInterface
    {
        return ServiceResolver::fromDefault(
            'escaper',
            EscaperInterface::class,
            context: 'PhalconKit tag helpers'
        );
    }
    
    /**
     * Prints the page title with optional prefixes and suffixes.
     *
     * @param bool $prepend [optional] Whether to prepend the page title with a prefix. Default is true.
     * @param bool $append [optional] Whether to append the page title with a suffix. Default is true.
     * @return void
     */
    public static function title(bool $prepend = true, bool $append = true): void
    {
        echo self::getTitle($prepend, $append);
    }
    
    /**
     * Implode the elements of an array using sprintf and optional glue.
     *
     * Example:
     * ```php
     * Tag::implodeSprintf(['class' => 'class1 class2', 'id' => 'my-id', 'test' => ['test1', 'test2']], '%2$s="%1$s"', ' ');
     * ```
     *
     * Result:
     * ```HTML
     * class="class1 class2" id="my-id" test="{['test1', 'test2']}"
     * ```
     *
     * @param array $array The input array.
     * @param string $format The format string to be used with sprintf. Default is '%s'.
     * @param string|null $glue The glue string to be used between array elements. Default is null.
     * @return string The resulting string after implodes. If the input array is empty, an empty string is returned.
     */
    public static function implodeSprintf(array $array, string $format = '%s', ?string $glue = null): string
    {
        $array = array_filter($array);
        return implode($glue ?? '', array_map(function ($value, $key) use ($format) {
            [$value, $key] = self::escapeParam($value, $key);
            return sprintf($format, $value, $key);
        }, $array, array_keys($array)));
    }
    
    /**
     * Escapes a parameter value based on the attribute.
     *
     * @param mixed $value The value to be escaped.
     * @param string|null $attr The attribute of the parameter. Default is null.
     * @param string $glue The delimiter to be used for joining array values. Default is a space (' ').
     * @return array An array containing the escaped value and the attribute.
     */
    public static function escapeParam(mixed $value = null, ?string $attr = null, string $glue = ' '): array
    {
        $escaper = ServiceResolver::fromDefault(
            'escaper',
            PhalconKitEscaperInterface::class,
            context: 'PhalconKit tag helpers'
        );
        
        if (!isset($value)) {
            return [$value, $attr];
        }
        
        if ($attr !== null) {
            $attr = $escaper->attributes($attr);
        }

        switch ($attr) {
            case 'css':
            case 'style':
                $value = $escaper->css($value);
                break;
            
            case 'js':
            case 'javascript':
                $value = $escaper->js($value);
                break;
            
            case 'href':
            case 'url':
                $value = $escaper->url($value);
                break;
            
            default:
                // array escaper
                if (is_array($value)) {
                    if (isset($value[0]) && is_string($value[0])) {
                        foreach ($value as &$v) {
                            $v = $escaper->attributes($v);
                        }
                        $value = implode($glue, $value);
                    }
                    
                    // deep array escaper
                    else {
                        $value = $escaper->json(json_encode($value));
                    }
                }
                // other object escaper
                else if (is_object($value)) {
                    $value = $escaper->json(json_encode($value));
                }
                // default escaper
                else {
                    $value = $escaper->attributes($value);
                }
                break;
        }
        
        return [$value, $attr];
    }
    
    /**
     * Retrieves the HTML representation of a specified tag, along with optional parameters and content.
     *
     * Example 1:
     *
     * ```php
     * Tag::get('div', ['class' => 'class1 class2'], ['content1', 'content2']);
     * ```
     *
     * ```HTML
     * <div class="class1 class2">content1</div>
     * <div class="class1 class2">content2</div>
     * ```
     *
     * Example 2:
     * ```php
     * Tag::get('div', ['class' => 'class1 class2'], ['content1', 'content2'], ' ');
     * ```
     *
     * ```HTML
     * <div class="class1 class2">content1 content2</div>
     * ```
     *
     * Example 3:
     * ```php
     * Tag::get('footer', ['class' => 'my-footer-class'], [
     *      Tag::get('ul', ['class' => 'my-ul-class'], [
     *          Tag::get('li', ['class'] => 'my-li-class', ['content1', 'content2']),
     *          Tag::get('li', ['class'] => 'my-li-class-2', ['content3', 'content4']),
     *      ],
     *      Tag::get('ul', ['class' => 'my-ul-class-2'], [
     *          Tag::get('li', ['class'] => 'my-li-class-3', ['content5', 'content6']),
     *      ], ''
     * ], ' ');
     * ```
     *
     * ```HTML
     * <footer class="my-footer-class">
     *      <ul class="my-ul-class">
     *          <li class="my-li-class">content1</li>
     *          <li class="my-li-class">content2</li>
     *          <li class="my-li-class-2">content3</li>
     *          <li class="my-li-class-2">content4</li>
     *      </ul>
     *      <ul class="my-ul-class-2">
     *          <li class="my-li-class-3">content5 content6</li>
     *      </ul>
     * </div>
     * ```
     *
     * @param string $tag The tag name.
     * @param array $params The optional parameters for the tag.
     * @param array $html The optional HTML content within the tag.
     * @param string|null $glue The optional string used to join multiple HTML elements. If not provided, the default value is used.
     * @return string The HTML representation of the tag, including parameters and content.
     */
    public static function get(string $tag, array $params = [], array $html = [], ?string $glue = null): string
    {
        $tagParams = self::getTagParams($tag, $params);
        
        // Tag field is mandatory
        $content = implode('', $html);
        
        $beforeHtml = '<' . $tag . $tagParams . (empty($content) ? '/' : '') . '>';
        $afterHtml = (empty($content)) ? '' : '</' . $tag . '>';
        $glue ??= $afterHtml . $beforeHtml;
        
        // Execute the tag generator
        return $beforeHtml . implode($glue, $html) . $afterHtml . PHP_EOL;
    }
    
    /**
     * Retrieves a formatted string representing a specific tag with the given parameters and HTML attributes.
     *
     * @param string $tag The name of the tag to retrieve.
     * @param array $params An optional array of parameters to pass as attributes to the tag.
     * @param array $html An optional array of HTML attributes to include in the tag.
     * @param string|null $glue An optional glue string for joining the HTML attributes.
     * @return string The formatted HTML string representing the tag with the provided parameters and attributes.
     */
    public static function getTag(string $tag, array $params = [], array $html = [], ?string $glue = null): string
    {
        return self::get($tag, $params, $html, $glue);
    }
    
    /**
     * Prints the HTML code for a given tag, with optional parameters and HTML attributes.
     *
     * @param string $tag The HTML tag to be printed.
     * @param array $params The parameters to be included in the HTML tag attributes.
     * @param array $html The HTML attributes to be included in the HTML tag.
     * @param ?string $glue The optional string used to concatenate the HTML attributes.
     * @return void
     */
    public static function tag(string $tag, array $params = [], array $html = [], ?string $glue = null): void
    {
        echo self::get($tag, $params, $html, $glue);
    }
    
    /**
     * Retrieves the tag parameters based on the given tag name, additional parameters, format string, and glue string.
     *
     * @param string $tag The name of the tag to retrieve the parameters for.
     * @param array $params (Optional) Additional parameters to merge with the tag attributes. The parameters should be provided as an associative array with keys representing the parameter names.
     * @param string $format (Optional) The format string to use when formatting the tag parameters. The format string should contain two placeholders: %1$s for the parameter value and %2$s for the parameter name.
     * @param string|null $glue (Optional) The glue string to use when joining the formatted tag parameters. If null, the default glue string will be used.
     * @return string The formatted tag parameters joined by the glue string.
     */
    public static function getTagParams(string $tag, array $params = [], string $format = ' %2$s="%1$s"', ?string $glue = null): string
    {
        return self::getParams(array_merge(self::getAttr($tag), $params), $format, $glue);
    }
    
    /**
     * Returns a formatted string containing tag parameters for the specified tag.
     *
     * @param string $tag The tag to be used.
     * @param array $params The parameters to be added to the tag. The parameters should be provided as an associative array with keys representing the parameter names and values representing the parameter values.
     * @param string $format The format string used to concatenate each parameter. The format string should contain two placeholders ('%1$s' and '%2$s') that will be replaced with the parameter value and name, respectively. The default format is ' %2$s="%1$s"'.
     * @param string|null $glue The glue used to concatenate multiple parameters. If null, the default PHP glue will be used.
     * @return void
     */
    public static function tagParams(string $tag, array $params = [], string $format = ' %2$s="%1$s"', ?string $glue = null): void
    {
        echo self::getTagParams($tag, $params, $format, $glue);
    }
    
    /**
     * Will sprintf an array_map of the array and then implode it with a ' ' glue
     * - Escape attrs and values during the process
     *
     * Example:
     * ```php
     * Tag::getParams(['class' => 'class1 class2', 'id' => 'my-id'])
     * ```
     *
     * Result:
     * ```HTML
     * class="class1 class2" id="my-id"
     * ```
     *
     * @param array $params Array to implode & sprintf
     * @return string Return the imploded sprintf "%2$s="%1$s" from an array
     */
    public static function getParams(array $params = [], string $format = ' %2$s="%1$s"', ?string $glue = null): string
    {
        foreach ($params as $param) {
            $params = array_merge_recursive(self::getAttr($param), $params);
        }
        $params = array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY);
        return self::implodeSprintf($params, $format, $glue);
    }
    
    /**
     * Prints the formatted parameters to the output.
     *
     * @param array $params An array containing the parameters to be formatted. The parameters should be provided as an associative array with keys representing the parameter names and values representing the parameter values.
     * @param string $format The format string used to format each parameter. The format string should contain two placeholders: %1$s representing the parameter value and %2$s representing the parameter name.
     * @param null|string $glue (optional) The string used to glue multiple formatted parameters together. If not provided, the default glue value will be used.
     * @return void
     */
    public static function params(array $params = [], string $format = ' %2$s="%1$s"', ?string $glue = null): void
    {
        echo self::getParams($params, $format, $glue);
    }
    
    /**
     * Retrieves the attributes of a specific name from the existing list of attributes.
     *
     * @param string $name The name of the attribute to retrieve.
     * @return array An array containing the attributes corresponding to the given name. If the attribute does not exist, an empty array is returned.
     */
    public static function getAttr(string $name): array
    {
        return self::getAttrs()[$name] ?? [];
    }
    
    /**
     * Retrieves the existing list of attributes.
     *
     * @return array Returns an array containing the existing list of attributes. The attributes are represented as key-value pairs.
     */
    public static function getAttrs(): array
    {
        return self::$attr;
    }
    
    /**
     * Sets attributes for a given name.
     *
     * @param string $name The name of the attribute.
     * @param array $attrs The attributes to be set for the given name. The attributes should be an associative array with keys representing the attribute names.
     * @param bool $merge Optional. Specifies whether to merge the new attributes with existing attributes. Default is true.
     * @return void
     */
    public static function setAttr(string $name, array $attrs = [], bool $merge = true): void
    {
        self::setAttrs([$name => $attrs], $merge);
    }
    
    /**
     * Sets attributes for the object.
     *
     * @param array $attrs An array of attributes to be set. Each attribute key represents the name of the attribute and the value represents the value of the attribute.
     * @param bool $merge Optional. Determines whether the existing attributes should be merged with the new attributes. Defaults to false.
     * @return array The updated attributes array after setting the new attributes.
     */
    public static function setAttrs(array $attrs = [], bool $merge = false): array
    {
        $ret = [];
        foreach ($attrs as $attrsKey => $attrsValue) {
            if (!$merge || empty(self::$attr[$attrsKey])) {
                $ret [$attrsKey] = self::$attr[$attrsKey] = $attrsValue;
            }
            else {
                $ret [$attrsKey] = self::$attr[$attrsKey] = array_merge_recursive(self::$attr[$attrsKey], $attrsValue);
            }
        }
        return $ret;
    }
    
    /**
     * Reset attributes
     *
     * @return void
     */
    public static function resetAttrs(): void
    {
        self::$attr = [];
    }
    
    /**
     * Set the meta charset value
     * Specifies the character encoding for the HTML document.
     *
     * Example:
     * ```php
     * Tag::setMetaCharset('UTF-8');
     * Tag::meta('charset', 'UTF-8');
     * ```
     *
     * Result:
     * ```HTML
     * <meta charset="UTF-8">
     * ```
     *
     * Common values:
     *      UTF-8 - Character encoding for Unicode
     *      ISO-8859-1 - Character encoding for the Latin alphabet
     * In theory, any character encoding can be used, but no browser understands all of them. The more widely a character encoding is used, the better the chance that a browser will understand it.
     *
     * To view all available character encodings, look at IANA character sets.
     * - @link http://www.iana.org/assignments/character-sets/character-sets.xhtml
     *
     * For more information about the charset values, please visit this documentation below
     * - @link https://www.w3schools.com/tags/att_meta_charset.asp
     */
    public static function setMetaCharset(string $charset = 'UTF-8'): void
    {
        self::removeMeta('charset');
        self::addMeta('charset', $charset);
    }
    
    /**
     * Sets the value of a meta-property.
     *
     * This method sets the specified meta-property with the given content. If there is
     * already a meta-property with the same name, it will be removed before adding the new one.
     *
     * @param string $property The name of the meta-property to set.
     * @param string|null $content The content to set for the meta-property. It can be null if the property doesn't require content.
     * @return void
     */
    public static function setMetaProperty(string $property, ?string $content = null): void
    {
        self::removeMeta('property', $property);
        self::addMeta('property', $property, $content);
    }
    
    /**
     * Set the meta-name attribute with the given value.
     * @param string $name The name of the meta-tag.
     * @param string|null $content The content of the meta-tag. Optional, defaults to null.
     * @return void
     */
    public static function setMetaName(string $name, ?string $content = null): void
    {
        self::removeMeta('name', $name);
        self::addMeta('name', $name, $content);
    }
    
    /**
     * Add meta
     *
     * @param string $attr The attribute of the meta-tag
     * @param string $value The value of the attribute
     * @param string|null $content (optional) The content of the meta-tag
     * @return void
     */
    public static function addMeta(string $attr, string $value, ?string $content = null): void
    {
        $meta = [$attr => $value];
        if (!empty($content)) {
            $meta['content'] = $content;
        }
        self::addRawMeta($meta);
    }
    
    /**
     * Add raw meta
     *
     * @param array $meta The meta-data to be added
     * @return void
     */
    public static function addRawMeta(array $meta): void
    {
        self::$meta [] = $meta;
    }
    
    /**
     * Add a new link tag
     * For more information about the attr, values, and other options please visite the w3schools documentation below
     * - https://www.w3schools.com/tags/tag_link.asp
     *
     * Example:
     * ```php
     * Tag::addLink('rel', 'alternate', ['type' => 'application/atom+xml', 'title' => 'Phalcon Kit', 'href' => '/blog/news/atom"']);
     * ```
     *
     * Result:
     * ```HTML
     * <link rel="alternate" type="application/atom+xml" title="Phalcon Kit News" href="/blog/news/atom">
     * ```
     *
     * @param string $attr Link tag attr
     * @param string $value Link tag attr value
     * @param array $options Link tag attrs and values
     * @return void
     */
    public static function addLink(string $attr, string $value, array $options = []): void
    {
        self::addRawLink(array_merge($options, [$attr => $value]));
    }
    
    /**
     * Adds a raw link to the existing list of links.
     *
     * @param array $link The raw link to be added. The link should be an associative array with keys representing the link attributes.
     * @return void
     */
    public static function addRawLink(array $link): void
    {
        self::$link [] = $link;
    }
    
    /**
     * Removes meta-tags from the existing list of meta-tags based on the specified attribute, value, and content.
     *
     * @param string $attr The attribute of the meta-tags to be removed.
     * @param string|null $value The value of the attribute. If null, all meta-tags with the specified attribute will be removed.
     * @param string|null $content The content of the meta-tags. If null, all meta-tags with the specified attribute and value will be removed.
     * @return void
     */
    public static function removeMeta(string $attr, ?string $value = null, ?string $content = null): void
    {
        if (isset(self::$meta[$attr]) && is_null($value)) {
            unset(self::$meta[$attr]);
        }
        else {
            foreach (self::$meta as $metaKey => $meta) {
                if (isset($meta[$attr]) && $meta[$attr] === $value) {
                    if (is_null($content) || isset($meta['content']) && $meta['content'] === $content) {
                        unset(self::$meta[$metaKey]);
                    }
                }
            }
        }
    }
    
    /**
     * Retrieves the concatenated string representation of the meta-values.
     *
     * @param string|null $glue The optional glue string used to concatenate the meta-values. Default is null.
     * @return string|null The concatenated string representation of the meta-values, or null if there are no meta-values.
     */
    public static function getMeta(?string $glue = null): ?string
    {
        $ret = [];
        foreach (self::$meta as $meta) {
            $ret [] = self::get('meta', $meta);
        }
        return implode($glue ?? '', $ret);
    }
    
    /**
     * Prints the meta-information using the specified glue.
     *
     * @param string|null $glue The glue to be used for concatenating the meta-information. If not provided, the default value is null.
     * @return void
     */
    public static function meta(?string $glue = null): void
    {
        echo self::getMeta($glue);
    }
    
    /**
     * Retrieves the links as a concatenated string with an optional glue separator.
     *
     * @param string|null $glue The separator used to concatenate the links. If null, no separator is used. Default value is null.
     * @return string|null The concatenated string of links, or null if there are no links.
     */
    public static function getLink(?string $glue = null): ?string
    {
        $ret = [];
        foreach (self::$link as $link) {
            $ret [] = self::get('link', $link);
        }
        return implode($glue ?? '', $ret);
    }
    
    /**
     * Generates and echoes the link string.
     *
     * @param string|null $glue The glue used to join the link attributes (optional). Default value is null.
     * @return void
     */
    public static function link(?string $glue = null): void
    {
        echo self::getLink($glue);
    }
    
    /**
     * Retrieves the CSS markup for the specified collection or all collections if no collection is specified.
     *
     * @param string|null $collection (optional) The name of the collection. If not specified, all collections will be included.
     * @return string|null The CSS markup for the specified collection or all collections. Returns null if no CSS is found.
     * @throws Exception
     */
    public static function getCss(?string $collection = null): ?string
    {
        $assets = self::getAssetsManager();
        $assets->useImplicitOutput(false);
        return $assets->outputCss($collection);
    }
    
    /**
     * Prints the CSS markup for the specified collection or the default collection if no collection is provided.
     *
     * If a collection is provided, it should be a string representing the name of the CSS collection.
     * If no collection is provided, the default collection will be used.
     *
     * @param string|null $collection The name of the CSS collection. Defaults to null.
     * @return void
     */
    public static function css(?string $collection = null): void
    {
        echo self::getCss($collection);
    }
    
    /**
     * Retrieves JavaScript markup from the given asset collection or from all asset collections if none is specified.
     *
     * @param string|null $collection Optional. The name of the asset collection. If not provided, JavaScript code from all asset collections will be retrieved.
     * @return string|null The generated JavaScript code, or null if no JavaScript code is found for the specified collection(s).
     * @throws Exception
     */
    public static function getJs(?string $collection = null): ?string
    {
        $assets = self::getAssetsManager();
        $assets->useImplicitOutput(false);
        return $assets->outputJs($collection);
    }
    
    /**
     * Outputs the JavaScript markup for a specific collection or for all collections if no collection is specified.
     *
     * @param string|null $collection The name of the collection to retrieve the JavaScript from. If null, all collections will be included.
     *
     * @return void
     * @throws Exception
     */
    public static function js(?string $collection = null): void
    {
        echo self::getJs($collection);
    }
}
