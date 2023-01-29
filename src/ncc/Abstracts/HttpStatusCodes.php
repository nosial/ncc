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

namespace ncc\Abstracts;

    abstract class HttpStatusCodes
    {
        const OK = 200;
        const CREATED = 201;
        const ACCEPTED = 202;
        const NO_CONTENT = 204;
        const MOVED_PERMANENTLY = 301;
        const FOUND = 302;
        const SEE_OTHER = 303;
        const NOT_MODIFIED = 304;
        const TEMPORARY_REDIRECT = 307;
        const PERMANENT_REDIRECT = 308;
        const BAD_REQUEST = 400;
        const UNAUTHORIZED = 401;
        const FORBIDDEN = 403;
        const NOT_FOUND = 404;
        const METHOD_NOT_ALLOWED = 405;
        const NOT_ACCEPTABLE = 406;
        const REQUEST_TIMEOUT = 408;
        const CONFLICT = 409;
        const GONE = 410;
        const LENGTH_REQUIRED = 411;
        const PRECONDITION_FAILED = 412;
        const PAYLOAD_TOO_LARGE = 413;
        const URI_TOO_LONG = 414;
        const UNSUPPORTED_MEDIA_TYPE = 415;
        const RANGE_NOT_SATISFIABLE = 416;
        const EXPECTATION_FAILED = 417;
        const IM_A_TEAPOT = 418;
        const MISDIRECTED_REQUEST = 421;
        const UNPROCESSABLE_ENTITY = 422;
        const LOCKED = 423;
        const FAILED_DEPENDENCY = 424;
        const UPGRADE_REQUIRED = 426;
        const PRECONDITION_REQUIRED = 428;
        const TOO_MANY_REQUESTS = 429;
        const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
        const UNAVAILABLE_FOR_LEGAL_REASONS = 451;
        const INTERNAL_SERVER_ERROR = 500;
        const NOT_IMPLEMENTED = 501;
        const BAD_GATEWAY = 502;
        const SERVICE_UNAVAILABLE = 503;
        const GATEWAY_TIMEOUT = 504;
        const HTTP_VERSION_NOT_SUPPORTED = 505;
        const VARIANT_ALSO_NEGOTIATES = 506;
        const INSUFFICIENT_STORAGE = 507;
        const LOOP_DETECTED = 508;
        const NOT_EXTENDED = 510;
        const NETWORK_AUTHENTICATION_REQUIRED = 511;

        const All = [
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