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

namespace PhalconKit\Http;

/**
 * HTTP status-code constants and reason-phrase lookup helpers.
 *
 * The list combines standard status codes with a few commonly encountered
 * vendor/proxy extension codes so framework code can avoid hard-coded numeric
 * literals in controllers, exceptions, and tests.
 *
 * Example:
 * ```php
 * StatusCode::getMessage(StatusCode::OK); // 'OK'
 * StatusCode::getMessage(200); // 'OK'
 * StatusCode::$messages[200]; // 'OK'
 * StatusCode::OK; // 200
 * ```
 *
 * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
 */
class StatusCode
{
    public const int CONTINUE = 100;
    public const int SWITCHING_PROTOCOLS = 101;
    public const int PROCESSING = 102;
    public const int OK = 200;
    public const int CREATED = 201;
    public const int ACCEPTED = 202;
    public const int NON_AUTHORITATIVE_INFORMATION = 203;
    public const int NO_CONTENT = 204;
    public const int RESET_CONTENT = 205;
    public const int PARTIAL_CONTENT = 206;
    public const int MULTI_STATUS = 207;
    public const int ALREADY_REPORTED = 208;
    public const int IM_USED = 226;
    public const int MULTIPLE_CHOICES = 300;
    public const int MOVED_PERMANENTLY = 301;
    public const int FOUND = 302;
    public const int SEE_OTHER = 303;
    public const int NOT_MODIFIED = 304;
    public const int USE_PROXY = 305;
    public const int SWITCH_PROXY = 306;
    public const int TEMPORARY_REDIRECT = 307;
    public const int PERMANENT_REDIRECT = 308;
    public const int BAD_REQUEST = 400;
    public const int UNAUTHORIZED = 401;
    public const int PAYMENT_REQUIRED = 402;
    public const int FORBIDDEN = 403;
    public const int NOT_FOUND = 404;
    public const int METHOD_NOT_ALLOWED = 405;
    public const int NOT_ACCEPTABLE = 406;
    public const int PROXY_AUTHENTICATION_REQUIRED = 407;
    public const int REQUEST_TIMEOUT = 408;
    public const int CONFLICT = 409;
    public const int GONE = 410;
    public const int LENGTH_REQUIRED = 411;
    public const int PRECONDITION_FAILED = 412;
    public const int REQUEST_ENTITY_TOO_LARGE = 413;
    public const int REQUEST_URI_TOO_LONG = 414;
    public const int UNSUPPORTED_MEDIA_TYPE = 415;
    public const int REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const int EXPECTATION_FAILED = 417;
    public const int IM_A_TEAPOT = 418;
    public const int AUTHENTICATION_TIMEOUT = 419;
    public const int METHOD_FAILURE = 420;
    public const int UNPROCESSABLE_ENTITY = 422;
    public const int LOCKED = 423;
    public const int FAILED_DEPENDENCY = 424;
    public const int UPGRADE_REQUIRED = 426;
    public const int PRECONDITION_REQUIRED = 428;
    public const int TOO_MANY_REQUESTS = 429;
    public const int REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const int LOGIN_TIMEOUT = 440;
    public const int NO_RESPONSE = 444;
    public const int RETRY_WITH = 449;
    public const int BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS = 450;
    public const int UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const int REQUEST_HEADER_TOO_LARGE = 494;
    public const int CERT_ERROR = 495;
    public const int NO_CERT = 496;
    public const int HTTP_TO_HTTPS = 497;
    public const int TOKEN_EXPIREDINVALID = 498;
    public const int CLIENT_CLOSED_REQUEST = 499;
    public const int INTERNAL_SERVER_ERROR = 500;
    public const int NOT_IMPLEMENTED = 501;
    public const int BAD_GATEWAY = 502;
    public const int SERVICE_UNAVAILABLE = 503;
    public const int GATEWAY_TIMEOUT = 504;
    public const int HTTP_VERSION_NOT_SUPPORTED = 505;
    public const int VARIANT_ALSO_NEGOTIATES = 506;
    public const int INSUFFICIENT_STORAGE = 507;
    public const int LOOP_DETECTED = 508;
    public const int BANDWIDTH_LIMIT_EXCEEDED = 509;
    public const int NOT_EXTENDED = 510;
    public const int NETWORK_AUTHENTICATION_REQUIRED = 511;
    public const int NETWORK_READ_TIMEOUT_ERROR = 598;
    public const int NETWORK_CONNECT_TIMEOUT_ERROR = 599;
    
    public const int FATAL_ERROR = 500;
    public const int MAINTENANCE = 503;
    public const int OVERLOADED = 503;
    public const int BUSY = 503;
    
    /**
     * Map status codes to their reason phrases.
     *
     * @var array<int, string>
     */
    public static array $messages = [
        self::CONTINUE => 'Continue',
        self::SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::PROCESSING => 'Processing',
        self::OK => 'OK',
        self::CREATED => 'Created',
        self::ACCEPTED => 'Accepted',
        self::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
        self::NO_CONTENT => 'No Content',
        self::RESET_CONTENT => 'Reset Content',
        self::PARTIAL_CONTENT => 'Partial Content',
        self::MULTI_STATUS => 'Multi-Status',
        self::ALREADY_REPORTED => 'Already Reported',
        self::IM_USED => 'IM Used',
        self::MULTIPLE_CHOICES => 'Multiple Choices',
        self::MOVED_PERMANENTLY => 'Moved Permanently',
        self::FOUND => 'Found',
        self::SEE_OTHER => 'See Other',
        self::NOT_MODIFIED => 'Not Modified',
        self::USE_PROXY => 'Use Proxy',
        self::SWITCH_PROXY => 'Switch Proxy',
        self::TEMPORARY_REDIRECT => 'Temporary Redirect',
        self::PERMANENT_REDIRECT => 'Permanent Redirect',
        self::BAD_REQUEST => 'Bad Request',
        self::UNAUTHORIZED => 'Unauthorized',
        self::PAYMENT_REQUIRED => 'Payment Required',
        self::FORBIDDEN => 'Forbidden',
        self::NOT_FOUND => 'Not Found',
        self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::NOT_ACCEPTABLE => 'Not Acceptable',
        self::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
        self::REQUEST_TIMEOUT => 'Request Timeout',
        self::CONFLICT => 'Conflict',
        self::GONE => 'Gone',
        self::LENGTH_REQUIRED => 'Length Required',
        self::PRECONDITION_FAILED => 'Precondition Failed',
        self::REQUEST_ENTITY_TOO_LARGE => 'Request Entity Too Large',
        self::REQUEST_URI_TOO_LONG => 'Request-URI Too Long',
        self::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
        self::REQUESTED_RANGE_NOT_SATISFIABLE => 'Requested Range Not Satisfiable',
        self::EXPECTATION_FAILED => 'Expectation Failed',
        self::IM_A_TEAPOT => 'I\'m a teapot',
        self::AUTHENTICATION_TIMEOUT => 'Authentication Timeout',
        self::METHOD_FAILURE => 'Method Failure',
        self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
        self::LOCKED => 'Locked',
        self::FAILED_DEPENDENCY => 'Failed Dependency',
        self::UPGRADE_REQUIRED => 'Upgrade Required',
        self::PRECONDITION_REQUIRED => 'Precondition Required',
        self::TOO_MANY_REQUESTS => 'Too Many Requests',
        self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        self::LOGIN_TIMEOUT => 'Login Timeout',
        self::NO_RESPONSE => 'No Response',
        self::RETRY_WITH => 'Retry With',
        self::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS => 'Blocked by Windows Parental Controls',
        self::UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
        self::REQUEST_HEADER_TOO_LARGE => 'Request Header Too Large',
        self::CERT_ERROR => 'Cert Error',
        self::NO_CERT => 'No Cert',
        self::HTTP_TO_HTTPS => 'HTTP to HTTPS',
        self::TOKEN_EXPIREDINVALID => 'Token expired/invalid',
        self::CLIENT_CLOSED_REQUEST => 'Client Closed Request',
        self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::NOT_IMPLEMENTED => 'Not Implemented',
        self::BAD_GATEWAY => 'Bad Gateway',
        self::SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::GATEWAY_TIMEOUT => 'Gateway Timeout',
        self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
        self::VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
        self::INSUFFICIENT_STORAGE => 'Insufficient Storage',
        self::LOOP_DETECTED => 'Loop Detected',
        self::BANDWIDTH_LIMIT_EXCEEDED => 'Bandwidth Limit Exceeded',
        self::NOT_EXTENDED => 'Not Extended',
        self::NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
        self::NETWORK_READ_TIMEOUT_ERROR => 'Network read timeout error',
        self::NETWORK_CONNECT_TIMEOUT_ERROR => 'Network connect timeout error',
    ];
    
    /**
     * Return the reason phrase for an HTTP status code.
     *
     * @param int $code HTTP status code.
     *
     * @return string|null Reason phrase, or null when the code is unknown.
     */
    public static function getMessage(int $code): ?string
    {
        return self::$messages[$code] ?? null;
    }
    
    /**
     * Return the HTTP status code for an exact reason phrase.
     *
     * Matching is case-sensitive and uses the reason phrases stored in
     * `StatusCode::$messages`.
     *
     * @param string $message Reason phrase to look up.
     *
     * @return int|null Status code, or null when the phrase is unknown.
     */
    public static function getCode(string $message): ?int
    {
        return array_flip(self::$messages)[$message] ?? null;
    }
    
    /**
     * Return a combined status line fragment such as `200 OK`.
     *
     * Unknown codes return the numeric code as a string because existing callers
     * and tests use this helper as a display formatter, not only as a strict
     * lookup.
     *
     * @param int $code HTTP status code.
     *
     * @return string|null Status code plus reason phrase, or the numeric code as
     *     a string when the phrase is unknown.
     */
    public static function getStatus(int $code): ?string
    {
        return trim($code . ' ' . self::getMessage($code));
    }
}
