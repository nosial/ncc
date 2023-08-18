<?php
/*
 * Copyright (c) Nosial 2022-2023, all rights reserved.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
 *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
 *  of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 *
 */

namespace ncc\Enums;

    final class HttpStatusCodes
    {
        public const OK = 200;
        public const CREATED = 201;
        public const ACCEPTED = 202;
        public const NO_CONTENT = 204;
        public const MOVED_PERMANENTLY = 301;
        public const FOUND = 302;
        public const SEE_OTHER = 303;
        public const NOT_MODIFIED = 304;
        public const TEMPORARY_REDIRECT = 307;
        public const PERMANENT_REDIRECT = 308;
        public const BAD_REQUEST = 400;
        public const UNAUTHORIZED = 401;
        public const FORBIDDEN = 403;
        public const NOT_FOUND = 404;
        public const METHOD_NOT_ALLOWED = 405;
        public const NOT_ACCEPTABLE = 406;
        public const REQUEST_TIMEOUT = 408;
        public const CONFLICT = 409;
        public const GONE = 410;
        public const LENGTH_REQUIRED = 411;
        public const PRECONDITION_FAILED = 412;
        public const PAYLOAD_TOO_LARGE = 413;
        public const URI_TOO_LONG = 414;
        public const UNSUPPORTED_MEDIA_TYPE = 415;
        public const RANGE_NOT_SATISFIABLE = 416;
        public const EXPECTATION_FAILED = 417;
        public const IM_A_TEAPOT = 418;
        public const MISDIRECTED_REQUEST = 421;
        public const UNPROCESSABLE_ENTITY = 422;
        public const LOCKED = 423;
        public const FAILED_DEPENDENCY = 424;
        public const UPGRADE_REQUIRED = 426;
        public const PRECONDITION_REQUIRED = 428;
        public const TOO_MANY_REQUESTS = 429;
        public const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
        public const UNAVAILABLE_FOR_LEGAL_REASONS = 451;
        public const INTERNAL_SERVER_ERROR = 500;
        public const NOT_IMPLEMENTED = 501;
        public const BAD_GATEWAY = 502;
        public const SERVICE_UNAVAILABLE = 503;
        public const GATEWAY_TIMEOUT = 504;
        public const HTTP_VERSION_NOT_SUPPORTED = 505;
        public const VARIANT_ALSO_NEGOTIATES = 506;
        public const INSUFFICIENT_STORAGE = 507;
        public const LOOP_DETECTED = 508;
        public const NOT_EXTENDED = 510;
        public const NETWORK_AUTHENTICATION_REQUIRED = 511;

        public const ALL = [
            self::OK,
            self::CREATED,
            self::ACCEPTED,
            self::NO_CONTENT,
            self::MOVED_PERMANENTLY,
            self::FOUND,
            self::SEE_OTHER,
            self::NOT_MODIFIED,
            self::TEMPORARY_REDIRECT,
            self::PERMANENT_REDIRECT,
            self::BAD_REQUEST,
            self::UNAUTHORIZED,
            self::FORBIDDEN,
            self::NOT_FOUND,
            self::METHOD_NOT_ALLOWED,
            self::NOT_ACCEPTABLE,
            self::REQUEST_TIMEOUT,
            self::CONFLICT,
            self::GONE,
            self::LENGTH_REQUIRED,
            self::PRECONDITION_FAILED,
            self::PAYLOAD_TOO_LARGE,
            self::URI_TOO_LONG,
            self::UNSUPPORTED_MEDIA_TYPE,
            self::RANGE_NOT_SATISFIABLE,
            self::EXPECTATION_FAILED,
            self::IM_A_TEAPOT,
            self::MISDIRECTED_REQUEST,
            self::UNPROCESSABLE_ENTITY,
            self::LOCKED,
            self::FAILED_DEPENDENCY,
            self::UPGRADE_REQUIRED,
            self::PRECONDITION_REQUIRED,
            self::TOO_MANY_REQUESTS,
            self::REQUEST_HEADER_FIELDS_TOO_LARGE,
            self::UNAVAILABLE_FOR_LEGAL_REASONS,
            self::INTERNAL_SERVER_ERROR,
            self::NOT_IMPLEMENTED,
            self::BAD_GATEWAY,
            self::SERVICE_UNAVAILABLE,
            self::GATEWAY_TIMEOUT,
            self::HTTP_VERSION_NOT_SUPPORTED,
            self::VARIANT_ALSO_NEGOTIATES,
            self::INSUFFICIENT_STORAGE,
            self::LOOP_DETECTED,
            self::NOT_EXTENDED,
            self::NETWORK_AUTHENTICATION_REQUIRED
        ];
    }